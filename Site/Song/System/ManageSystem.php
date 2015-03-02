<?php
/**
 * Managed by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song\System;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\Map\MapRenderer;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Validation\RequiredValidation;
use CPath\Request\Validation\URLValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\SiteMap;
use Site\Song\System\DB\SystemEntry;

class ManageSystem implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Manage System Information';

    const FORM_ACTION = '/manage/system/:name';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'manage-system';

    const PARAM_SYSTEM_NAME = 'name';
    const PARAM_SYSTEM_DESCRIPTION = 'description';
    const PARAM_SUBMIT = 'submit';
    const PARAM_SYSTEM_STATUS = 'status';
    const TIP_MANAGE_SYSTEM = '<b>Edit System Information</b><br /><br />Add a system description';

    private $tag;

    public function __construct($systemTag) {
        $this->tag = $systemTag;
    }

    /**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws \Exception
	 * @return IResponse the execution response
	 */
	function execute(IRequest $Request) {
        $OldEntry = SystemEntry::query($this->tag)->fetch();
        $SystemEntry = $OldEntry ?: new SystemEntry($this->tag);

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
            new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),

            new HTMLElement('fieldset', 'fieldset-system-info inline',
                new HTMLElement('legend', 'legend-system-info', 'System Info'),

                new MapRenderer($SystemEntry)
            ),


            new HTMLElement('fieldset', 'fieldset-system-manage inline',
                new HTMLElement('legend', 'legend-system-manage', 'Manage'),

                new HTMLPathTip($Request, '#tip-select', self::TIP_MANAGE_SYSTEM),

                new HTMLElement('label', null, "System Name:<br/>",
                    new HTMLInputField(self::PARAM_SYSTEM_NAME, $SystemEntry->getName(),
                        new Attributes('disabled', 'disabled')
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "System Description:<br/>",
                    new HTMLTextAreaField(self::PARAM_SYSTEM_DESCRIPTION, $SystemEntry->getDescription(),
                        new Attributes('rows', 10, 'cols', 40),
                        new Attributes('placeholder', 'i.e. "System Description"')
                    )
                ),

                "<br/>Allowed Tags:<br/>",
                "<div class='info'>&#60;" . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;</div>',

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
            )
        );

        if (!$Request instanceof IFormRequest)
            return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        switch ($submit) {
            case 'update':
                $name = $Form->validateField($Request, self::PARAM_SYSTEM_NAME);
                $description = $Form->validateField($Request, self::PARAM_SYSTEM_DESCRIPTION);
//                $status = $Form->validateField($Request, self::PARAM_SYSTEM_STATUS);
                $SystemEntry->update($Request, $description);
                return new RedirectResponse(ManageSystem::getRequestURL($SystemEntry->getName()), "Updated System successfully. Such the artisan...", 5);

            default:
                throw new \InvalidArgumentException("Invalid submit: " . $submit);
        }
    }

	// Static

	public static function getRequestURL($system) {
		return str_replace(':' . self::PARAM_SYSTEM_NAME, urlencode($system), self::FORM_ACTION);
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
		return new ExecutableRenderer(new static(urldecode($Request[self::PARAM_SYSTEM_NAME])), true);
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