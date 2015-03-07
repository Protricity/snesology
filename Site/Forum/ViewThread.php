<?php
/**
 * Viewd by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Forum;

use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\HTML\HTMLConfig;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use CPath\Request\Validation\Exceptions\ValidationException;
use CPath\Request\Validation\RegexValidation;
use CPath\Request\Validation\RequiredValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\Guest\GuestAccount;
use Site\Account\Guest\TestAccount;
use Site\Account\Session\DB\SessionEntry;
use Site\Account\ViewAccount;
use Site\Config;
use Site\Forum\DB\ThreadEntry;
use Site\Forum\DB\ThreadTable;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\SiteMap;

class ViewThread implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'View a new Thread';

    const FORM_ACTION = '/t/:path';
    const FORM_ACTION2 = '/view/thread/:path';
    const FORM_ACTION3 = '/forum/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'view-thread';

    const PARAM_PATH = 'path';
    const PARAM_THREAD_TITLE = 'title';
    const PARAM_THREAD_CONTENT = 'content';
    const PARAM_THREAD_STATUS = 'status';

    private $path;

    public function __construct($path=null) {
        $this->path = $path;
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

        $Account = AccountEntry::loadFromSession($SessionRequest);

        $path = $this->path;
        if(strpos($path, '#') !== false)
            list($path) = explode('#', $path);

		$Form = new HTMLForm(self::FORM_METHOD, self::getRequestURL($path), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
//			new HTMLHeaderScript(__DIR__ . '/assets/Thread.js'),
//			new HTMLHeaderStyleSheet(__DIR__ . '/assets/Thread.css'),

            empty($path) ? null :
            new HTMLElement('fieldset', 'fieldset-view-parent-threads',
                new HTMLElement('legend', 'legend-parent-threads', "Parent Threads: " . dirname($path)),

                function(IRequest $Request) use ($path) {

                    $Query = ThreadEntry::query()
                        ->orderBy(ThreadTable::COLUMN_PATH . " ASC", '');

                    while($path) {
                        $path = dirname($path);
                        if($path === '\\')
                            $path = '/';
                        else
                            $path = rtrim($path, '/') . '/';

                        $Query->orWhere(ThreadTable::COLUMN_PATH, $path);

                        if(!$path || $path === '/')
                            break;
                    }

                    echo RI::ni(), "<ul class='thread'>";
                    while ($ThreadEntry = $Query->fetch()) {
                        /** @var ThreadEntry $ThreadEntry */
                        $ThreadEntry->renderHTML($Request);
                    }
                    echo RI::ni(), "</ul>";
                }
            ),

            new HTMLElement('fieldset', 'fieldset-view-thread',
                new HTMLElement('legend', 'legend-thread', "Current: " . $this->path),

                function(IRequest $Request) use ($path) {
                    $Query = ThreadEntry::query()
                        ->orderBy(ThreadTable::COLUMN_PATH . " ASC, " . ThreadTable::COLUMN_CREATED . " DESC", '')
                        ->where(ThreadTable::COLUMN_PATH, ($path ?: '/'));

                    echo RI::ni(), "<ul class='thread'>";
                    while ($ThreadEntry = $Query->fetch()) {
                        /** @var ThreadEntry $ThreadEntry */
                        $ThreadEntry->renderHTML($Request);
                    }
                    echo RI::ni(), "</ul>";
                }
            ),

            new HTMLElement('fieldset', 'fieldset-view-child-threads',
                new HTMLElement('legend', 'legend-child-thread', "Branches: " . $this->path . '*'),

                function(IRequest $Request) use ($path) {
                    $Query = ThreadEntry::query()
                        ->orderBy(ThreadTable::COLUMN_PATH . " ASC, " . ThreadTable::COLUMN_CREATED . " DESC", '')
                        ->where(ThreadTable::COLUMN_PATH, ($path ?: '/') . '#reply', ' != ?')
                        ->where(ThreadTable::COLUMN_PATH . ' REGEXP "^' . ($path ?: '/') . '[^/]+/$"');

                    echo RI::ni(), "<ul class='child-threads'>";
                    while ($ThreadEntry = $Query->fetch()) {
                        /** @var ThreadEntry $ThreadEntry */
                        $ThreadEntry->renderHTML($Request);
                    }
                    echo RI::ni(), "</ul>";
                }
            ),


            new HTMLElement('fieldset', 'fieldset-view-replies',
                new HTMLElement('legend', 'legend-replies', "Replies: " . $path . '#reply'),

                function(IRequest $Request) use ($path) {
                    $Query = ThreadEntry::query()
                        ->orderBy(ThreadTable::COLUMN_PATH . " ASC, " . ThreadTable::COLUMN_CREATED . " DESC", '')
                        ->where(ThreadTable::COLUMN_PATH, ($path ?: '/') . '#reply');

                    /** @var ThreadEntry $ThreadEntry */
                    $ThreadEntry = $Query->fetch();

                    echo RI::ni(), "<ul class='replies'>";
                    while ($ThreadEntry) {
                        $ThreadEntry->renderHTML($Request);
                        $ThreadEntry = $Query->fetch();
                    }
                    echo RI::ni(), "</ul>";


                    echo RI::ni(), "</ul>";
                }
            ),

            "<br/>",
			new HTMLElement('fieldset', 'fieldset-create-thread inline',
				new HTMLElement('legend', 'legend-thread', "New Thread/Reply"),

                new HTMLElement('label', null, "Thread Path:<br/>",
                    new HTMLInputField(self::PARAM_PATH, $this->path,
                        new Attributes('placeholder', 'i.e. "/discussion/mytopic/mythread/#myreply"'),
                        new Attributes('size', '32'),
                        new RequiredValidation(),
                        new RegexValidation('/[a-z0-9\/_#-]+/', "Threads may only contain alpha-numeric characters and the special characters '/' and '#'")
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Thread Title (Optional):<br/>",
                    new HTMLInputField(self::PARAM_THREAD_TITLE,
                        new Attributes('placeholder', 'i.e. "My Thread"'),
                        new Attributes('size', '32')
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Thread Content:<br/>",
                    new HTMLTextAreaField(self::PARAM_THREAD_CONTENT,
                        new Attributes('placeholder', 'Enter your post content'),
                        new Attributes('rows', 10, 'cols', 40),
                        new RequiredValidation()
                    )
                ),

                "<br/>",
                new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags'),

				"<br/><br/>",
				new HTMLButton('submit', 'Post', 'submit')
			),
			"<br/>"
		);

		if(!$Request instanceof IFormRequest)
			return $Form;

//        $Form->setFormValues($Request);

        $path = $Form->validateField($Request, self::PARAM_PATH);
        $title = $Form->validateField($Request, self::PARAM_THREAD_TITLE);
        $content = $Form->validateField($Request, self::PARAM_THREAD_CONTENT);

        if(!$path = trim($path, '/'))
            throw new ValidationException($Form, "Invalid Path");
        $path = '/' . $path . '/';
        if(strpos($path, '#') !== false)
            $path = rtrim($path, '/');

        if($Account->getName() === GuestAccount::PGP_NAME) {
            if (strpos($path, 'public') === false || $path === 'public') {
                throw new ValidationException($Form, "This is not a public forum. Account required");
            }
        }

        $id = ThreadEntry::create($Request, $Account->getFingerprint(), $path, $title, $content);
        $Response = new RedirectResponse(ViewThread::getRequestURL($path), "What rolls down stairs, alone or in pairs, and over your neighbor's dog?, what's great for a snack, and fits on your back?", 2);
        $Response->setData('id', $id);
        $Response->setData('path', $path);
        return $Response;
	}

	// Static

    public static function getRequestURL($path, $subPath = null) {
        if(strpos($path, '#') !== false)
            list($path) = explode('#', $path);
        return str_replace(':' . self::PARAM_PATH, urlencode(rtrim($path, '/') . '/' . ltrim($subPath, '/')), self::FORM_ACTION);
    }
    public static function getBranchRequestURL($path=null, $branchPath = 'branch') {
        return self::getRequestURL($path, $branchPath);
    }
    public static function getReplyRequestURL($path) {
        return self::getRequestURL($path, '#reply');
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
        $path = isset($Request[self::PARAM_PATH]) ? urldecode($Request[self::PARAM_PATH]) : null;
        return new ExecutableRenderer(new static($path), true);
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
        $RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION2, __CLASS__);
        $RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION3, __CLASS__, IRequest::NAVIGATION_ROUTE, 'Forum');
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

        $ViewThread = new ViewThread('test-thread');

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_THREAD_TITLE, 'test-thread-title');
        $Test->setRequestParameter(self::PARAM_THREAD_CONTENT, 'test-thread-content');
        $ViewThread->execute($Test);

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_THREAD_TITLE, 'test-thread-title');
        $Test->setRequestParameter(self::PARAM_THREAD_CONTENT, 'test-thread-content');
        $ViewThread->execute($Test);

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_THREAD_TITLE, 'test-thread-title');
        $Test->setRequestParameter(self::PARAM_THREAD_CONTENT, 'test-thread-content');
        $ViewThread->execute($Test);

        ThreadEntry::table()->delete(ThreadTable::COLUMN_PATH, 'test-thread');

    }
}