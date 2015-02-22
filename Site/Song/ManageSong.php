<?php
/**
 * Managed by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLSelectField;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLHeaderScript;
use CPath\Render\HTML\Header\HTMLHeaderStyleSheet;
use CPath\Render\HTML\Header\HTMLMetaTag;
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
use Site\Song\DB\SongEntry;
use Site\SiteMap;
use Site\Song\DB\SongGenreEntry;
use Site\Song\DB\SongSystemEntry;


class ManageSong implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Manage a new Song';

	const FORM_ACTION = '/manage/song/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'manage-song';

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
		$SessionRequest = $Request;
		if (!$SessionRequest instanceof ISessionRequest)
			throw new \Exception("Session required");

        $Song = SongEntry::get($this->id);

        $systemList = $Song->getGenreList();
        $genreList = $Song->getSystemList();

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/song.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/song.css'),

			new HTMLElement('fieldset', 'fieldset-manage-song',
				new HTMLElement('legend', 'legend-song', self::TITLE),

				new HTMLElement('label', null, "<br/>",
					$SystemSelect = new HTMLSelectField(self::PARAM_SONG_SYSTEMS, $systemList,
						new RequiredValidation()
					)
				),

				"<br/><br/>",
                new HTMLElement('label', null, "<br/>",
                    $GenreSelect = new HTMLSelectField(self::PARAM_SONG_GENRES, $genreList,
                        new RequiredValidation()
                    )
                ),

				"<br/><br/>Update:<br/>",
				new HTMLButton('update', 'Update', 'update')
			),
			"<br/>"
		);

		if(!$Request instanceof IFormRequest)
			return $Form;

        $genres = $Form->validateField($Request, self::PARAM_SONG_GENRES);
        $systems = $Form->validateField($Request, self::PARAM_SONG_SYSTEMS);
        $title = $Form->validateField($Request, self::PARAM_SONG_TITLE);

        foreach($genres as $genre) {
            if(in_array($genre, $genreList)) {
                $genreList = array_diff($genreList, array($genre));
            } else {
                SongGenreEntry::addToSong($Request, $Song->getID(), $genre);
            }
        }

        foreach($genreList as $genre) {
            SongGenreEntry::removeFromSong($Request, $Song->getID(), $genre);
        }

        foreach($systems as $system) {
            if(in_array($system, $genreList)) {
                $systemList = array_diff($systemList, array($system));
            } else {
                SongSystemEntry::addToSong($Request, $Song->getID(), $system);
            }
        }

        foreach($systemList as $system) {
            SongSystemEntry::removeFromSong($Request, $Song->getID(), $system);
        }

        if($title !== $Song->getTitle()) {
            $Song->updateTitle($Request, $title);
        }

        return new RedirectResponse(ManageSong::getRequestURL($Song->getID()), "Updated song successfully. Redirecting...", 5);
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
    }
}