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
use CPath\Data\Date\DateUtil;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HeaderConfig;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Render\HTML\HTMLConfig;
use CPath\Render\HTML\HTMLContainer;
use CPath\Render\HTML\HTMLMimeType;
use CPath\Render\HTML\HTMLResponseBody;
use CPath\Render\HTML\IHTMLValueRenderer;
use CPath\Request\IRequest;
use CPath\Response\IResponse;
use CPath\Response\IResponseHeaders;
use CPath\Response\ResponseRenderer;
use CPath\Route\HTML\HTMLRouteNavigator;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\Route\RouteIndex;
use CPath\Route\RouteRenderer;
use Site\Account\AccountHome;
use Site\Account\DB\AccountEntry;
use Site\Account\Guest\GuestAccount;
use Site\Account\ViewAccount;
use Site\Config;
use Site\Path\ManagePath;
use Site\PGP\PGPSupportHeaders;
use Site\Relay\HTML\HTMLRelayChat;
use Site\Relay\PathLog;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\SiteMap;
use Site\Song\Artist\ViewArtist;
use Site\Song\DB\SongEntry;
use Site\Song\Genre\ViewGenre;
use Site\Song\ManageSong;
use Site\Song\Review\DB\ReviewEntry;
use Site\Song\ReviewSong;
use Site\Song\System\ViewSystem;
use Site\Song\ViewSong;

class DefaultTemplate extends HTMLContainer implements IRoutable, IBuildable {

	const META_PATH = 'path';
	const META_SESSION = 'session';
	const META_SESSION_ID = 'session-id';
	const META_DOMAIN_PATH = 'domain-path';

	/** @var HTMLElement */
	private $mHeader;
	/** @var HTMLElement */
	private $mHeaderTitle;
	/** @var HTMLElement */
	private $mNavBar;
    /** @var HTMLElement */
    private $mPathBar;

    public function __construct($_content=null) {

		$Render = new HTMLResponseBody(
			$this->mHeader = new HTMLElement('section', 'header',
				$this->mHeaderTitle = new HTMLElement('h1', 'header-title')
			),
			$Content = new HTMLElement('section', 'content',
                $this->mNavBar = new HTMLElement('div', 'navbar'

                ),
                $this->mPathBar = new HTMLElement('div', 'pathbar'

                )
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
	 * @build --disable 0
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$RouteBuilder = new RouteBuilder($Request, new SiteMap(), '__default_template');
		$RouteBuilder->writeRoute('ANY *', __CLASS__);
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
        if(!$Request->getMimeType() instanceof HTMLMimeType)
            return false;

		static $customLoaded = false;
		$customLoaded ?: HTMLConfig::addValueRenderer(new CustomHTMLValueRenderer($Request));
		$customLoaded = true;


		$class = Config::$TemplateClass;
		/** @var DefaultTemplate $Template */
		$Template = new $class();

		$Object = reset($Previous);
		if($RouteRenderer instanceof RouteRenderer) {
			if(!$Object)
				$Object = new RouteIndex($Request, $RouteRenderer);

            $Account = AccountEntry::loadFromSession($Request);

//			$NavBarTitle = new HTMLElement('h3', 'navbar-title');
//			$Template->mNavBar->
            $Navigator = new HTMLRouteNavigator($RouteRenderer);
            $Navigator->addClass($Account->getName() === GuestAccount::PGP_NAME ? IRequest::NAVIGATION_NO_LOGIN_CLASS : IRequest::NAVIGATION_LOGIN_ONLY_CLASS);
			$Template->mNavBar->addContent($Navigator);
		}

		if ($Object instanceof IResponseHeaders) {
			$Object->sendHeaders($Request);

		} else if ($Object instanceof IResponse) {
			$ResponseRenderer = new ResponseRenderer($Object);
			$ResponseRenderer->sendHeaders($Request);
		}

		header('Cache-Control: private, max-age=0, no-cache, must-revalidate, no-store, proxy-revalidate');
		header('X-Location: ' . $_SERVER['REQUEST_URI']);

		$Template->mHeaderTitle->addAll(
			'BETA - ' . $Request->getMethodName() . ' ' . $Request->getPath()
		);
        $Template->mPathBar->addAll($Request->getPath());

		$Template->addMetaTag(HTMLMetaTag::META_CONTENT_TYPE, 'text/html; charset=utf-8');
		$Template->addMetaTag(self::META_PATH, $Request->getPath());
		$Template->addMetaTag(self::META_DOMAIN_PATH, $Request->getDomainPath(false));

		$Template->addAll($Object);

		for($i=1; $i<sizeof($Previous); $i++)
			$Template->addAll($Previous[$i]);

		$Template->addSupportHeaders(
		//new HTMLAjaxSupportHeaders(),
			new PGPSupportHeaders()
		);
        foreach(HTMLConfig::getSupportHeaders() as $Headers)
            $Template->addSupportHeaders($Headers);
//
//        foreach($SubPaths as $SubPath) {
//            $processed = false;
//            foreach($Previous as $Obj) {
//                if($Obj instanceof IProcessSubPaths) {
//                    $processed = $Obj->processSubPath($SubPath) ?: $processed;
//                }
//                if($Obj instanceof IHTMLContainer) {
//                    foreach($Obj->getContentRecursive() as $Content) {
//                        if($Content instanceof IProcessSubPaths) {
//                            $processed = $Content->processSubPath($SubPath) ?: $processed;
//                        }
//                    }
//                }
//            }
//            if(!$processed)
//                $Template->mNavBar->addContent($SubPath);
//        }

		$Template->renderHTML($Request);
		return true;
	}
}

class CustomHTMLValueRenderer implements IHTMLValueRenderer, IHTMLSupportHeaders {
    private $Request;

	function __construct(IRequest $Request) {
		$this->Request = $Request;
	}


	/**
	 * @param $key
	 * @param $value
	 * @param null $arg1
	 * @return bool if true, the value has been rendered, otherwise false
	 */
	function renderNamedValue($key, $value, $arg1=null) {
		switch($key) {
            case 'song-created':
			case 'created':
				if($value)
					echo DateUtil::elapsedTime($value);
				return true;

            case 'account':
            case 'song-entry-account':
            case 'invite-fingerprint':
            case 'review-fingerprint':
            case 'inviter':
            case 'fingerprint':
                $domain = $this->Request->getDomainPath();
                $arg1 ?: $arg1 = '..' . substr($value, -8);
                $href = $domain . ltrim(ViewAccount::getRequestURL($value), '/');
                echo "<a href='{$href}'>", $arg1 ?: $value, "</a>";
                return true;

            case 'id':
                $domain = $this->Request->getDomainPath();
                switch(true) {
                    case AccountEntry::ID_PREFIX === $value[0]: $url = ViewAccount::getRequestURL($value); break;
                    case SongEntry::ID_PREFIX === $value[0]: $url = ManageSong::getRequestURL($value); break;
                    case ReviewEntry::ID_PREFIX === $value[0]: $url = ReviewSong::getRequestURL($value); break;
                    default: $url = $value;
                }
                $href = $domain . ltrim($url, '/');
                echo "<a href='{$href}'>", $arg1 ?: $value, "</a>";
                return true;

//            case 'review':
//            case 'review-id':
//                $domain = $this->Request->getDomainPath();
//                $href = $domain . ltrim(ReviewSong::getRequestURL($value), '/');
//                echo "<a href='{$href}'>", $arg1 ?: $value, "</a>";
//                return true;

            case 'song-title':
                if($arg1) {
                    $domain = $this->Request->getDomainPath();
                    $href = $domain . ltrim(ViewSong::getRequestURL($arg1), '/');
                    echo "<a href='{$href}'>", $value, "</a>";
                } else {
                    echo $value;
                }
                return true;

            case 'description':
            case 'song-review':
            case 'song-description':
                if($value) {
                    $PopUp = new HTMLPopUpBox($value, HTMLPopUpBox::CLASS_DESCRIPTION);
                    $PopUp->renderHTML($this->Request);
                }
                return true;

            case 'public-key':
                if($value) {
                    $PopUp = new HTMLPopUpBox(nl2br(trim($value)), HTMLPopUpBox::CLASS_DESCRIPTION);
                    $PopUp->renderHTML($this->Request);
                }
                return true;

            case 'artist':
            case 'song-artist':
            case 'artist-name':
                $domain = $this->Request->getDomainPath();
                foreach(explode(', ', $value) as $i => $artist) {
                    $href = $domain . ltrim(ViewArtist::getRequestURL($artist), '/');
                    echo $i > 0 ? ', ' : '';
                    echo "<a href='{$href}'>", $artist, "</a>";
                }
                return true;

            case 'genre':
            case 'song-genre':
            case 'genre-name':
                $domain = $this->Request->getDomainPath();
                foreach(explode(', ', $value) as $i => $genre) {
                    $href = $domain . ltrim(ViewGenre::getRequestURL($genre), '/');
                    echo $i > 0 ? ', ' : '';
                    echo "<a href='{$href}'>", $genre, "</a>";
                }
                return true;

            case 'system':
            case 'song-system':
            case 'system-name':
                $domain = $this->Request->getDomainPath();
                foreach(explode(', ', $value) as $i => $system) {
                    $href = $domain . ltrim(ViewSystem::getRequestURL($system), '/');
                    echo $i > 0 ? ', ' : '';
                    echo "<a href='{$href}'>", $system, "</a>";
                }
                return true;

            case 'url':
            case 'tag-url':
            case 'tag-url-torrent':
            case 'tag-url-download':
            case 'tag-url-icon':
            case 'url-cover-front':
            case 'url-cover-back':
                $href = $value;
                echo "<a href='{$href}'>", $arg1 ?: 'link', "</a>";
                return true;

            case 'song-url:origin':
                $href = $value;
                echo "<a href='{$href}'>", $arg1 ?: 'Origin', "</a>";
                return true;

            case 'path':
                $domain = $this->Request->getDomainPath();
                $href = $domain . ltrim(ManagePath::getRequestURL($value), '/');
                echo "<a href='{$href}'>", $arg1 ?: $value, "</a>";
                return true;

		}
		return false;
	}

	/**
	 * @param $value
	 * @return bool if true, the value has been rendered, otherwise false
	 */
	function renderValue($value) {
		return false;
	}

    /**
     * Write all support headers used by this renderer
     * @param IRequest $Request
     * @param IHeaderWriter $Head the writer inst to use
     * @return void
     */
    function writeHeaders(IRequest $Request, IHeaderWriter $Head) {
        $PopupBox = new HTMLPopUpBox(null);
        $PopupBox->writeHeaders($Request, $Head);
    }
}