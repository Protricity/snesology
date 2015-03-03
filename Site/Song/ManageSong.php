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
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Attribute\StyleAttributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLCheckBoxField;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLSelectField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
use CPath\Render\HTML\Element\HTMLAnchor;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLHeaderScript;
use CPath\Render\HTML\Header\HTMLHeaderStyleSheet;
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
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\Register;
use Site\Config;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\Request\DB\RequestEntry;
use Site\SiteMap;
use Site\Song\DB\SongEntry;
use Site\Song\DB\SongTable;
use Site\Song\Review\HTML\HTMLSongReviewsTable;
use Site\Song\Tag\DB\TagEntry;


class ManageSong implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Manage a Song';

	const FORM_ACTION = '/manage/song/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'manage-song';

    const PARAM_SONG_ID = 'id';
    const PARAM_SONG_TITLE = 'title';
    const PARAM_SONG_DESCRIPTION = 'description';
//    const PARAM_SONG_GENRES = 'genres';
//    const PARAM_SONG_SYSTEMS = 'systems';
    const PARAM_SONG_TAG_NAME = 'tag-name';
    const PARAM_SONG_TAG_VALUE = 'tag-value';
    const PARAM_SUBMIT = 'submit';
    const PARAM_SONG_REMOVE_TAG = 'remove-tag';

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

        $Account = AccountEntry::loadFromSession($SessionRequest);

        $Song = SongEntry::get($this->id);

//        $systemList = SystemEntry::getAll();
//        $genreList = GenreEntry::getAll();
        $tagList = TagEntry::$TagDefaults;

//        $oldGenres = $Song->getGenreList();
//        $oldSystems = $Song->getSystemList();

        $ReviewTable = new HTMLSongReviewsTable($Request, $Song->getID());

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/song.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/song.css'),

            new HTMLElement('fieldset', 'fieldset-manage-song inline',
                new HTMLElement('legend', 'legend-song', self::TITLE),

                new HTMLElement('label', null, "Song Title:<br/>",
                    new HTMLInputField(self::PARAM_SONG_TITLE, $Song->getTitle(),
                        new Attributes('placeholder', 'Edit Song Title'),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Description:<br/>",
                    new HTMLTextAreaField(self::PARAM_SONG_DESCRIPTION, $Song->getDescription(),
                        new Attributes('placeholder', 'Enter a song description'),
                        new Attributes('rows', 10, 'cols', 40),
                        new RequiredValidation()
                    )
                ),

                "<br/>",
                new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags'),


//                "<br/><br/>",
//                new HTMLElement('label', null, "Game Systems:<br/>",
//                    $SelectSystem = new HTMLSelectField(self::PARAM_SONG_SYSTEMS . '[]', $systemList,
//                        new Attributes('multiple', 'multiple'),
//                        new RequiredValidation()
//                    )
//                ),
//
//                "<br/><br/>",
//                new HTMLElement('label', null, "Genres:<br/>",
//                    $SelectGenre = new HTMLSelectField(self::PARAM_SONG_GENRES . '[]', $genreList,
//                        new Attributes('multiple', 'multiple'),
//                        new RequiredValidation()
//                    )
//                ),

                "<br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
            ),

            ($Song->hasFlags(SongEntry::STATUS_PUBLISHED)
                ? null
                : new HTMLElement('fieldset', 'fieldset-manage-song-publish inline',
                    new HTMLElement('legend', 'legend-song-publish', "Publish!"),

                    "Tags in place? <br/> All ready to go? <br/><br/>",
                    new HTMLButton(self::PARAM_SUBMIT, 'Publish "' . $Song->getTitle() . '"', 'publish')
                )
            ),

            new HTMLElement('fieldset', 'fieldset-manage-song-tags-add inline',
                new HTMLElement('legend', 'legend-song-tags-add', "Add Tag"),

                new HTMLElement('label', null, "Tag Name<br/>",
                    new HTMLSelectField(self::PARAM_SONG_TAG_NAME, $tagList
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Tag Value<br/>",
                    new HTMLInputField(self::PARAM_SONG_TAG_VALUE
                    )
                ),

//                new HTMLCheckBoxField(self::PARAM_SONG_TAG_VALUE,
//                    new Attributes('disabled', 'disabled')
//                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Add Tag', 'add-song-tag')
            ),

            new HTMLElement('fieldset', 'fieldset-manage-song-tags-remove inline',
                new HTMLElement('legend', 'legend-song-tags-remove', "Remove Tag"),

                new HTMLElement('label', null, "Tag Name<br/>",
                    $SelectRemoveTag = new HTMLSelectField(self::PARAM_SONG_REMOVE_TAG,
                        array("Select a tag to remove" => null),
                        new StyleAttributes('width', '15em')
                    )
                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Remove Tag', 'remove-song-tag')
            ),

            new HTMLElement('fieldset', 'fieldset-song-info inline',
                new HTMLElement('legend', 'legend-song-info', "Song Information"),

                new MapRenderer($Song)
            ),

            new HTMLElement('fieldset', 'fieldset-manage-song-reviews inline',
                new HTMLElement('legend', 'legend-song-reviews', "Song Reviews"),

                $ReviewTable,

                new HTMLAnchor(ReviewSong::getRequestURL($Song->getID()), "Add a review")
            )
		);


//        foreach($oldSystems as $system)
//            $SelectSystem->addOption($system, $system, true);

//        foreach($oldGenres as $genre)
//            $SelectGenre->addOption($genre, $genre, true);

        foreach($Song->getTagList() as $tag) {
            list($tagName, $tagValue) = $tag;
            $title = array_search($tagName, TagEntry::$TagDefaults) ?: $tagName;
            $SelectRemoveTag->addOption($tagName.':'.$tagValue, "{$title} - {$tagValue}");
        }

		if(!$Request instanceof IFormRequest)
			return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        RequestEntry::createFromRequest($Request, $Account);

        switch($submit) {
            case 'add-song-tag':
                $tagName = $Form->validateField($Request, self::PARAM_SONG_TAG_NAME);
                $tagValue = $Form->validateField($Request, self::PARAM_SONG_TAG_VALUE);
                $Song->addTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ManageSong::getRequestURL($Song->getID()), "Updated song successfully. Misdirecting...", 5);

            case 'remove-song-tag':
                list($tagName, $tagValue) = explode(':', $Form->validateField($Request, self::PARAM_SONG_REMOVE_TAG), 2);
                $Song->removeTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ManageSong::getRequestURL($Song->getID()), "Removed tag successfully. Reexploding...", 5);

            case 'publish':
                $Song->publish($Request, $Form);
                return new RedirectResponse(ManageSong::getRequestURL($Song->getID()), "Song published successfully. You rock", 5);

            case 'update':
//                $newGenres = $Form->validateField($Request, self::PARAM_SONG_GENRES);
//                $newSystems = $Form->validateField($Request, self::PARAM_SONG_SYSTEMS);
                $newTitle = $Form->validateField($Request, self::PARAM_SONG_TITLE);
                $newDescription = $Form->validateField($Request, self::PARAM_SONG_DESCRIPTION);

//                foreach($newGenres as $genre) {
//                    if(in_array($genre, $oldGenres)) {
//                        $oldGenres = array_diff($oldGenres, array($genre));
//                    } else {
//                        SongGenreEntry::addToSong($Request, $Song->getID(), $genre);
//                    }
//                }

//                foreach($oldGenres as $genre) {
//                    SongGenreEntry::removeFromSong($Request, $Song->getID(), $genre);
//                }

//                foreach($newSystems as $system) {
//                    if(in_array($system, $oldSystems)) {
//                        $oldSystems = array_diff($oldSystems, array($system));
//                    } else {
//                        SongSystemEntry::addToSong($Request, $Song->getID(), $system);
//                    }
//                }
//
//                foreach($oldSystems as $system) {
//                    SongSystemEntry::removeFromSong($Request, $Song->getID(), $system);
//                }

                if($newTitle !== $Song->getTitle()
                 || $newDescription !== $Song->getDescription()) {
                    $Song->update($Request, $newTitle, $newDescription);
                }

                return new RedirectResponse(ManageSong::getRequestURL($Song->getID()), "Updated song successfully. Redissecting...", 5);

            default:
                throw new \InvalidArgumentException("Invalid submit: " . $submit);
        }

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

    /**
     * Perform a unit test
     * @param IUnitTestRequest $Test the unit test request inst for this test session
     * @return void
     * @test --disable 0
     * Note: Use doctag 'test' with '--disable 1' to have this ITestable class skipped during a build
     */
    static function handleStaticUnitTest(IUnitTestRequest $Test) {
        $Session = &$Test->getSession();
        $TestAccount = new AccountEntry('78E02897', Register::TEST_PUBLIC_KEY);
        $Session[AccountEntry::SESSION_KEY] = serialize($TestAccount);

        SongEntry::table()->delete(SongTable::COLUMN_TITLE, 'test-song-title');

        $CreateSong = new CreateSong();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(CreateSong::PARAM_SONG_TITLE, 'test-song-title');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_ARTIST, 'test-song-artist');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_SIMILAR, 'test-song-similar');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_ORIGINAL, 'test-song-original');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_CHIP_STYLE, '8-bit');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_DESCRIPTION, 'test-song-description');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_GENRE, array('test-song-genre'));
        $Test->setRequestParameter(CreateSong::PARAM_SONG_SYSTEM, array('test-song-system'));
        $CreateSong->execute($Test);

        $id = $CreateSong->getNewSongID();

        $ManageSong = new ManageSong($id);

        $Test->clearRequestParameters();
        $Test->setRequestParameter(ManageSong::PARAM_SONG_TITLE, 'test-song-title2');
        $Test->setRequestParameter(ManageSong::PARAM_SONG_DESCRIPTION, 'test-song-description2');
        $Test->setRequestParameter(ManageSong::PARAM_SUBMIT, 'update');
        $ManageSong->execute($Test);

        SongEntry::delete($Test, $id);
    }
}