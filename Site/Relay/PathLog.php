<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Relay;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\ArrayKeyMapper;
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLHeaderScript;
use CPath\Render\HTML\Header\HTMLHeaderStyleSheet;
use CPath\Render\HTML\IRenderHTML;
use CPath\Render\IRenderAll;
use CPath\Request\Exceptions\RequestException;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use CPath\Request\Validation\RequiredValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Response\Response;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\Guest\GuestAccount;
use Site\Account\Guest\TestAccount;
use Site\Account\Session\AccountSession;
use Site\Account\Session\DB\SessionEntry;
use Site\Account\ViewAccount;
use Site\Relay\DB\RelayLogEntry;
use Site\Relay\DB\RelayLogTable;
use Site\Relay\Socket\SocketRequest;
use Site\SiteMap;
use Wrench\Connection;

class PathLog implements IExecutable, IBuildable, IRoutable, ITestable
{
    const FORM_CLASS = 'form-relay-log';
	const FORM_ACTION = '/relay/:path';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'relay-log';

    const PARAM_LOG = 'log';
    const PARAM_PATH = 'path';
    const LOG_CONTAINER = 'log-container';
    const USER_LIST_CONTAINER = 'user-list-container';

    static private $Channels = array();

    private $path;

    public function __construct($path) {
        $this->path = $path;
    }

    private function say(SocketRequest $Request, AccountEntry $Account, Response $LogResponse) {
        $path = $LogResponse->getData('path');
        if(!isset(self::$Channels[$path])) {
            self::$Channels[$path] = array();
        }

        $channel = &self::$Channels[$path];

        $connection = $Request->getSocketConnection();
        if(!isset($channel[$connection->getId()])) {
            // Join
            $channel[$connection->getId()] = array($connection, $Account);
        }

        $old = $channel;
        foreach($old as $id => $info) {
            /** @var Connection $conn*/
            list($conn) = $info;

            if(!$conn->getSocket()->isConnected()) {
                // Disconnected
                unset($channel[$id]);
                continue;
            }

            $array = ArrayKeyMapper::mapToArray($LogResponse);
            $json = json_encode($array);
            $conn->send($json);
        }
    }

    /**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws \Exception
	 * @return IResponse the execution response
	 */
	function execute(IRequest $Request) {
		$SessionRequest = $Request;
		if (!$SessionRequest instanceof ISessionRequest)
			throw new \Exception("Session required");

        $path = $this->path;

        $users = array();

		$Form = new HTMLForm(self::FORM_METHOD, self::getRequestURL($path), self::FORM_NAME, self::FORM_CLASS,
//			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/relay.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/relay.css'),

			$FieldSetLog = new HTMLElement('fieldset', 'fieldset-log inline small',
                new HTMLElement('legend', 'legend-relay-log', "Relay Chat Room: " . $path),

                "<div class='" . self::LOG_CONTAINER . "'>",
                function() use ($path, &$users) {
                    if($path) {
                        $Query = RelayLogEntry::query()
                            ->where(RelayLogTable::COLUMN_PATH, $path);

                        while($LogEntry = $Query->fetch()) {
                            /** @var RelayLogEntry $LogEntry */
                            $users[$LogEntry->getAccountFingerprint()] = $LogEntry->getAccountName() ?: substr($LogEntry->getAccountFingerprint(), -8);
                            echo RI::ni(), '<div class="relay-log">',
                                RI::ni(1), '<span class="relay-account">',
                                    RI::ni(2), '<a href="', ViewAccount::getRequestURL($LogEntry->getAccountFingerprint()) ,'">',
                                        $LogEntry->getAccountName() ?: $LogEntry->getAccountFingerprint(),
                                    "</a>",
                                RI::ni(1), "</span> ",
                                $LogEntry->getLog(),
                            '</div>';
                        }
                    }
                },
                "</div>",
                "<div class='" . self::USER_LIST_CONTAINER . "'>",
                function() use ($path, &$users) {
                    foreach($users as $fingerprint => $name) {
                        echo RI::ni(), '<div class="relay-account">',
                            '<a href="', ViewAccount::getRequestURL($fingerprint) ,'">', $name, "</a>",
                        RI::ni(), "</div>";
                    }
                },
                "</div>"
            )
		);

        $Account = AccountEntry::loadFromSession($SessionRequest);

        if($Account->getName() === GuestAccount::PGP_NAME) {
            if(strpos($path, 'public') === false)
                return $Form;

        } else {

        }

        $FieldSetLog->addAll(
            "<br/>",
            new HTMLInputField(self::PARAM_LOG,
//                    new Attributes('placeholder', 'i.e. "sup giez"'),
                new RequiredValidation()
            ),
            new HTMLButton('submit', 'Send', 'submit')
        );

        if(!$Request instanceof IFormRequest)
			return $Form;

        $Form->setFormValues($Request);

        $log = $Form->validateField($Request, self::PARAM_LOG);

		RelayLogEntry::create($Request, $path, $Account->getFingerprint(), $log);

        $Response = new RedirectResponse(PathLog::getRequestURL($path), "Log Entered", 5);
        $Response->setData('log', $log);
        $Response->setData('path', PathLog::getRequestURL($path));
        $Response->setData('account', $Account);

        if($Request instanceof SocketRequest) {
            $this->say($Request, $Account, $Response);
        }

        return $Response;
	}

	// Static

	public static function getRequestURL($path=null) {
        return str_replace(':' . self::PARAM_PATH, urlencode($path), self::FORM_ACTION);
	}

	/**
	 * Route the request to this class object and return the object
	 * @param IRequest $Request the IRequest inst for this render
	 * @param array|null $Previous all previous response object that were passed from a handler, if any
	 * @param null|mixed $_arg [varargs] passed by route map
	 * @return void|bool|Object returns a response object
	 * If nothing is returned (or bool[true]), it is assumed that rendering has occurred and the request ends
	 * If false is returned, this static handler will be called again if another handler returns an object
	 * If an object is returned, it is passed along to the next handler
	 */
	static function routeRequestStatic(IRequest $Request, Array &$Previous = array(), $_arg = null) {
		$Render = new ExecutableRenderer(new static(urldecode($Request[self::PARAM_PATH])), true);
		$Render->execute($Request);
		return $Render;
	}

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 0
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$RouteBuilder = new RouteBuilder($Request, new SiteMap());
		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION, __CLASS__);
	}

    /**
     * Perform a unit test
     * @param IUnitTestRequest $Test the unit test request inst for this test session
     * @return void
     * @test --disable 0
     * Note: Use doctag 'test' with '--disable 1' to have this ITestable class skipped during a build
     */
    static function handleStaticUnitTest(IUnitTestRequest $Test) {
        SessionEntry::create($Test, TestAccount::PGP_FINGERPRINT);

        $CreatePath = new PathLog('test-path');

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_LOG, 'test-content');
        $CreatePath->execute($Test);

        RelayLogEntry::table()->delete(RelayLogTable::COLUMN_PATH, 'test-path');
    }
}

