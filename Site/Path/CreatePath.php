<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Path;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLSelectField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
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
use Site\Account\Guest\TestAccount;
use Site\Account\Session\DB\SessionEntry;
use Site\Path\DB\PathEntry;
use Site\Path\DB\PathTable;
use Site\Request\DB\RequestEntry;
use Site\SiteMap;

class CreatePath implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Create a new Path';

	const FORM_ACTION = '/create/path/';
	const FORM_ACTION2 = '/paths/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'create-path';

    const PARAM_PATH = 'path';
    const PARAM_PATH_TITLE = 'path-title';
    const PARAM_PATH_CONTENT = 'path-content';
    const PARAM_PATH_STATUS = 'path-status';

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

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
//			new HTMLHeaderScript(__DIR__ . '/assets/path.js'),
//			new HTMLHeaderStyleSheet(__DIR__ . '/assets/path.css'),

			new HTMLElement('fieldset', 'fieldset-create-path inline',
				new HTMLElement('legend', 'legend-path', self::TITLE),

                new HTMLElement('label', null, "New Path:<br/>",
                    new HTMLInputField(self::PARAM_PATH, (isset($Request[self::PARAM_PATH]) ? $Request[self::PARAM_PATH] : null),
                        new Attributes('placeholder', 'i.e. "/register/#tips"'),
                        new RequiredValidation(),
                        new RegexValidation('/[a-z0-9\/_#-]+/', "Paths may only contain alpha-numeric characters and the special characters '/' and '#'")
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Path Title:<br/>",
                    new HTMLInputField(self::PARAM_PATH_TITLE,
                        new Attributes('placeholder', 'i.e. "Registration Tips"'),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Path Content:<br/>",
                    new HTMLTextAreaField(self::PARAM_PATH_CONTENT,
                        new Attributes('placeholder', 'Enter content for this path'),
                        new Attributes('rows', 10, 'cols', 40),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Status:<br/>",
                    $SelectStatus = new HTMLSelectField(self::PARAM_PATH_STATUS . '[]', PathEntry::$StatusOptions,
                        new Attributes('multiple', 'multiple')
                    )
                ),

				"<br/><br/>",
				new HTMLButton('submit', 'Create', 'submit')
			),
			"<br/>"
		);

		if(!$Request instanceof IFormRequest)
			return $Form;

        $Form->setFormValues($Request);

        $path = $Form->validateField($Request, self::PARAM_PATH);
        $title = $Form->validateField($Request, self::PARAM_PATH_TITLE);
        $content = $Form->validateField($Request, self::PARAM_PATH_CONTENT);
        $status = $Form->validateField($Request, self::PARAM_PATH_STATUS);
        $status = array_sum((array)$status);

        $MatchingPath = PathEntry::table()
            ->select()
            ->where(PathTable::COLUMN_PATH, $path)
            ->fetch();

        if($MatchingPath)
            throw new \InvalidArgumentException("A published path already has this name. What gives!?");

        RequestEntry::createFromRequest($Request, $Account);

        PathEntry::create($Request, $path, $title, $content, $status);

        return new RedirectResponse(ManagePath::getRequestURL($path), "Path created successfully. How empathic is that...", 5);
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
		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION2, __CLASS__);
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

        $CreatePath = new CreatePath();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_PATH, 'test-path');
        $Test->setRequestParameter(self::PARAM_PATH_TITLE, 'test-path-title');
        $Test->setRequestParameter(self::PARAM_PATH_CONTENT, 'test-path-content');
        $CreatePath->execute($Test);

        PathEntry::table()->delete(PathTable::COLUMN_PATH, 'test-path');

    }
}