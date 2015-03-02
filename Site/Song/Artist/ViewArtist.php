<?php
/**
 * Viewd by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song\Artist;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\IRequest;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\SiteMap;
use Site\Song\Review\HTML\HTMLArtistSongsTable;
use Site\Song\Review\HTML\HTMLSongReviewsTable;

class ViewArtist implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'View Artist Information';

    const FORM_ACTION = '/sa/:id';
    const FORM_ACTION2 = '/song/artist/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'view-artist';

    const PARAM_ARTIST = 'id';
    const PARAM_Artist_TITLE = 'title';
    const PARAM_Artist_GENRES = 'genres';
    const PARAM_Artist_SYSTEMS = 'systems';

    private $tag;

    public function __construct($artistTag) {
        $this->tag = $artistTag;
    }

    /**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws \Exception
	 * @return IResponse the execution response
	 */
	function execute(IRequest $Request) {

        $ArtistSongs = new HTMLArtistSongsTable($this->tag);

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),

            new HTMLElement('fieldset',
                new HTMLElement('legend', 'legend-artist-songs', self::TITLE),

                $ArtistSongs
            )
		);

		return $Form;
	}

	// Static

	public static function getRequestURL($artist) {
		return str_replace(':' . self::PARAM_ARTIST, urlencode($artist), self::FORM_ACTION);
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
		return new ExecutableRenderer(new static(urldecode($Request[self::PARAM_ARTIST])), true);
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