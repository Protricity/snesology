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
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use CPath\Request\Validation\RegexValidation;
use CPath\Request\Validation\RequiredValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Relay\DB\RelayLogEntry;
use Site\Relay\DB\RelayLogTable;
use Site\SiteMap;
use Site\Path\DB\PathEntry;
use Site\Path\DB\PathTable;

class CreateLogEntry implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Create a new Log Entry';

	const FORM_ACTION = '/create/relay-log';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'create-relay-log';

    const PARAM_PATH = 'path';
    const PARAM_PATH_CONTENT = 'path-content';

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

        $Account = AccountEntry::loadFromSession($SessionRequest);

        $path = null;
        if(isset($Request[self::PARAM_PATH]))
            $path = $Request[self::PARAM_PATH];

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
//			new HTMLHeaderScript(__DIR__ . '/assets/path.js'),
//			new HTMLHeaderStyleSheet(__DIR__ . '/assets/path.css'),

			new HTMLElement('fieldset', 'fieldset-create-relay-log inline',
				new HTMLElement('legend', 'legend-relay-log', self::TITLE),

                new HTMLElement('label', null, "Log Path:<br/>",
                    new HTMLInputField(self::PARAM_PATH, $path,
                        new Attributes('placeholder', 'i.e. "/chat/public"'),
                        new RequiredValidation(),
                        new RegexValidation('/[a-z0-9\/_#-]+/', "Paths may only contain alpha-numeric characters and the special characters '/' and '#'")
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Log Entry:<br/>",
                    new HTMLInputField(self::PARAM_PATH_CONTENT,
                        new Attributes('placeholder', 'i.e. "sup giez"'),
                        new RequiredValidation()
                    )
                ),

				"<br/><br/>",
				new HTMLButton('submit', 'Create', 'submit')
			),

			"<br/><br/>",
			new HTMLElement('fieldset', 'fieldset-create-relay-log inline',
                new HTMLElement('legend', 'legend-relay-log', "Log: " . $path),

                function() use ($path) {
                    if($path) {
                        $Query = RelayLogEntry::query()
                            ->where(RelayLogTable::COLUMN_PATH, $path);

                        while($LogEntry = $Query->fetch()) {
                            /** @var RelayLogEntry $LogEntry */
                            echo RI::ni(), '<div class="relay-log"><span class="relay-account">', $LogEntry->getAccountName(), "</span> ", $LogEntry->getLog(), '</div>';
                        }
                    }
                },

                new HTMLInputField(self::PARAM_PATH_CONTENT,
                    new Attributes('placeholder', 'i.e. "sup giez"'),
                    new RequiredValidation()
                ),
                new HTMLButton('submit', 'Send', 'submit')
            )
		);

		if(!$Request instanceof IFormRequest)
			return $Form;

        $Form->setFormValues($Request);

        $path = $Form->validateField($Request, self::PARAM_PATH);
        $content = $Form->validateField($Request, self::PARAM_PATH_CONTENT);

		RelayLogEntry::create($Request, $path, $Account->getFingerprint(), $content);

        return new RedirectResponse(CreateLogEntry::getRequestURL($path), "What rolls down stairs, alone or in pairs, and over your neighbor's dog?, what's great for a snack, and fits on your back?", 5);
	}

	// Static

	public static function getRequestURL($newPath=null) {
		return self::FORM_ACTION . ($newPath ? '?' . self::PARAM_PATH . '=' . urlencode($newPath) : '');
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
		$Render = new ExecutableRenderer(new static(), true);
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
        $Session = &$Test->getSession();
        $TestAccount = new AccountEntry('test-fp');
        $Session[AccountEntry::SESSION_KEY] = serialize($TestAccount);

        $CreatePath = new CreateLogEntry();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_PATH, 'test-path');
        $Test->setRequestParameter(self::PARAM_PATH_CONTENT, 'test-content');
        $CreatePath->execute($Test);

        RelayLogEntry::table()->delete(RelayLogTable::COLUMN_PATH, 'test-path');
    }
}