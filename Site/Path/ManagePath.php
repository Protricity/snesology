<?php
/**
 * Managed by PhpStorm.
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
use CPath\Render\Map\MapRenderer;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use CPath\Request\Validation\RequiredValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Path\DB\PathEntry;
use Site\SiteMap;

class ManagePath implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Manage a Path';

	const FORM_ACTION = '/manage/path/:path';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'manage-path';

    const PARAM_PATH = 'path';
    const PARAM_PATH_TITLE = 'path-title';
    const PARAM_PATH_CONTENT = 'path-content';
    const PARAM_PATH_STATUS = 'path-status';
    const PARAM_SUBMIT = 'submit';

    private $path;

    public function __construct($path) {
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

        $Path = PathEntry::get($this->path);

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
//			new HTMLHeaderScript(__DIR__ . '/assets/path.js'),
//			new HTMLHeaderStyleSheet(__DIR__ . '/assets/path.css'),

            new HTMLElement('fieldset', 'fieldset-manage-path inline',
                new HTMLElement('legend', 'legend-path', self::TITLE),

                new HTMLElement('label', null, "Edit Path:<br/>",
                    new HTMLInputField(self::PARAM_PATH, $Path->getPath(),
                        new Attributes('disabled', 'disabled')
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Path Title:<br/>",
                    new HTMLInputField(self::PARAM_PATH_TITLE, $Path->getTitle(),
                        new Attributes('placeholder', 'i.e. "Registration Tips"'),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Path Content:<br/>",
                    new HTMLTextAreaField(self::PARAM_PATH_CONTENT, $Path->getContent(),
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
                new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
            ),

            ($Path->hasFlags(PathEntry::STATUS_ACTIVE)
                ? null
                : new HTMLElement('fieldset', 'fieldset-manage-path-activate inline',
                    new HTMLElement('legend', 'legend-path-activate', "Activate"),

                    "Got everything in there? <br/> Ready to path it? <br/><br/>",
                    new HTMLButton(self::PARAM_SUBMIT, 'Activate path', 'activate')
                )
            ),

            new HTMLElement('fieldset', 'fieldset-path-info inline',
                new HTMLElement('legend', 'legend-path-info', "Path Information"),

                new MapRenderer($Path)
            )
		);

        foreach(PathEntry::$StatusOptions as $desc => $flag)
            if($Path->hasFlags($flag))
                $SelectStatus->addOption($flag, $desc, true);

        if(!$Request instanceof IFormRequest)
			return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        switch($submit) {
            case 'activate':
                $Path->activate($Request, $Form);
                return new RedirectResponse(ManagePath::getRequestURL($Path->getPath()), "Path activated successfully. Thanks!", 5);

            case 'update':
                $newTitle = $Form->validateField($Request, self::PARAM_PATH_TITLE);
                $newContent = $Form->validateField($Request, self::PARAM_PATH_CONTENT);
                $newStatus = $Form->validateField($Request, self::PARAM_PATH_STATUS);
                $newStatus = array_sum($newStatus);

                $Path->update($Request, $newTitle, $newContent, $newStatus);

                return new RedirectResponse(ManagePath::getRequestURL($Path->getPath()), "Updated path successfully. Homeopathying...", 5);

            default:
                throw new \InvalidArgumentException("Invalid submit: " . $submit);
        }

	}

	// Static

	public static function getRequestURL($path) {
        $path = urlencode($path);
        return str_replace(':' . self::PARAM_PATH, $path, self::FORM_ACTION);
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
		$path = $Request[self::PARAM_PATH];
        $path = urldecode($path);

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
    }
}