<?php
/**
 * Viewd by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\HTML\IHTMLContainer;
use CPath\Render\Map\MapRenderer;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\IRequest;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Relay\HTML\HTMLRelayChat;
use Site\SiteMap;
use Site\Song\DB\SongEntry;
use Site\Song\Review\HTML\HTMLSongReviewsTable;

class ViewSong implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'View Song';

    const FORM_ACTION = '/s/:id';
    const FORM_ACTION2 = '/view/song/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'view-song';

    const PARAM_SONG_ID = 'id';
    const PARAM_SONG_TITLE = 'title';
    const PARAM_SONG_GENRES = 'genres';
    const PARAM_SONG_SYSTEMS = 'systems';

    private $id;

    public function __construct($songID) {
        $this->id = $songID;
    }

    /**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws \Exception
	 * @return IResponse the execution response
	 */
	function execute(IRequest $Request) {
        $Song = SongEntry::get($this->id);

        $ReviewTable = new HTMLSongReviewsTable($Request, $Song->getID());

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
            
            new MapRenderer($Song),
			"<br/>",
            $ReviewTable
		);

        $Form->addContent(new HTMLRelayChat($Request, 'public-song-' . $Song->getID()), IHTMLContainer::KEY_RENDER_CONTENT_AFTER);

		return $Form;
	}

	// Static

	public static function getRequestURL($songID) {
		return str_replace(':' . self::PARAM_SONG_ID, $songID, self::FORM_ACTION);
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
		return new ExecutableRenderer(new static($Request[self::PARAM_SONG_ID]), true);
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