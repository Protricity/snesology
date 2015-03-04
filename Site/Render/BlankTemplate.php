<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 10/20/14
 * Time: 11:23 PM
 */
namespace Site\Render;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HeaderConfig;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\HTML\HTMLContainer;
use CPath\Render\HTML\HTMLResponseBody;
use CPath\Request\IRequest;
use CPath\Response\IResponse;
use CPath\Response\IResponseHeaders;
use CPath\Response\ResponseRenderer;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\Route\RouteIndex;
use CPath\Route\RouteRenderer;
use Site\Config;
use Site\SiteMap;

class BlankTemplate extends HTMLContainer implements IRoutable, IBuildable {

	const META_PATH = 'path';
	const META_SESSION = 'session';
	const META_SESSION_ID = 'session-id';
	const META_DOMAIN_PATH = 'domain-path';

	/** @var HTMLElement */
	private $mHeader;
	/** @var HTMLElement */
	private $mHeaderTitle;

	public function __construct($_content=null) {

		$Render = new HTMLResponseBody(
			$this->mHeader = new HTMLElement('section', 'header',
				$this->mHeaderTitle = new HTMLElement('h1', 'header-title')
			),
			$Content = new HTMLElement('section', 'content centered'
			),
			$Footer = new HTMLElement('section', 'footer',
				new HTMLElement('div', 'logos')
			)
		);

		parent::__construct($Render);
		$this->setContainer($Content);
		$Render->addSupportHeaders($this);
		$this->addHeaderScript(HeaderConfig::$JQueryPath);
		$this->addHeaderScript(__DIR__ . '/assets/default-template.js');
		$this->addHeaderStyleSheet(__DIR__ . '/assets/default-template.css');

		$this->addAll(func_get_args());
	}

	// Static

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 1
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$RouteBuilder = new RouteBuilder($Request, new SiteMap(), '__blank_template');
		$RouteBuilder->writeRoute('ANY /order', __CLASS__);
	}

	/**
	 * Route the request to this class object and return the object
	 * @param IRequest $Request the IRequest inst for this render
	 * @param Object[]|null $Previous all previous response object that were passed from a handler, if any
	 * @param RouteRenderer|null $RouteRenderer
	 * @param array $args
	 * @return void|bool|Object returns a response object
	 * If nothing is returned (or bool[true]), it is assumed that rendering has occurred and the request ends
	 * If false is returned, this static handler will be called again if another handler returns an object
	 * If an object is returned, it is passed along to the next handler
	 */
	static function routeRequestStatic(IRequest $Request, Array &$Previous = array(), $RouteRenderer=null, $args=array()) {
		$Template = new BlankTemplate();

		$Object = reset($Previous);
		if($RouteRenderer instanceof RouteRenderer) {
			if(!$Object)
				$Object = new RouteIndex($Request, $RouteRenderer);

//			$NavBarTitle = new HTMLElement('h3', 'navbar-title');
//			$Template->mNavBar->
//			$Template->mNavBar->addContent(new HTMLRouteNavigator($RouteRenderer));
		}


		if ($Object instanceof IResponseHeaders) {
			$Object->sendHeaders($Request);

		} else if ($Object instanceof IResponse) {
			$ResponseRenderer = new ResponseRenderer($Object);
			$ResponseRenderer->sendHeaders($Request);
		}

		header('Cache-Control: private, max-age=0, no-cache, must-revalidate, no-store, proxy-revalidate');
		header('X-Location: ' . $_SERVER['REQUEST_URI']);

		$Template->addMetaTag(HTMLMetaTag::META_CONTENT_TYPE, 'text/html; charset=utf-8');
		$Template->addMetaTag(self::META_PATH, $Request->getPath());
		$Template->addMetaTag(self::META_DOMAIN_PATH, $Request->getDomainPath(false));

		$Template->addAll($Object);

		for($i=1; $i<sizeof($Previous); $i++)
			$Template->addAll($Previous[$i]);

		$Template->renderHTML($Request);
		return true;
	}
}
