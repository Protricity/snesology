<?php
/**
 * Viewd by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song\System;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\StyleAttributes;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\HTMLAnchor;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\Map\MapRenderer;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\IRequest;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\SiteMap;
use Site\Song\System\HTML\HTMLSystemSongsTable;
use Site\Song\System\DB\SystemEntry;

class ViewSystem implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'View System Information';

    const FORM_ACTION = '/ss/:name';
    const FORM_ACTION2 = '/song/system/:name';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'view-system';

    const PARAM_SYSTEM_NAME = 'name';

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
        $SystemEntry = SystemEntry::query($this->tag)->fetch() ?: new SystemEntry($this->tag);
        $SystemSongs = new HTMLSystemSongsTable($this->tag);

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),

            new HTMLElement('fieldset', 'fieldset-system-info inline',
                new HTMLElement('legend', 'legend-system-info', 'System Info'),

                new MapRenderer($SystemEntry),
                "<br/>",
                new HTMLAnchor(ManageSystem::getRequestURL($this->tag), "Edit",
                    new StyleAttributes('float', 'right')
                )
            ),

            new HTMLElement('fieldset',
                new HTMLElement('legend', 'legend-system-songs', 'Related Songs'),

                $SystemSongs
            )
		);

		return $Form;
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
        $RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION2, __CLASS__);
    }
}