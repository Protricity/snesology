<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLSelectField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
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
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\SiteMap;
use Site\Song\DB\SongEntry;
use Site\Song\DB\SongTable;
use Site\Song\Defaults\DefaultChipStyles;
use Site\Song\Genre\DB\GenreEntry;
use Site\Song\System\DB\SystemEntry;
use Site\Song\Tag\DB\SongTagEntry;

class CreateSong implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Create a new Song Entry';

	const FORM_ACTION = '/create/song/';
	const FORM_ACTION2 = '/songs/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'create-song';

    const PARAM_SONG_TITLE = 'song-title';
    const PARAM_SONG_ARTIST = 'song-artist';
    const PARAM_SONG_GENRE = 'song-genre';
    const PARAM_SONG_SYSTEM = 'song-system';
    const PARAM_SONG_DESCRIPTION = 'song-description';
    const TIPS_CREATE_SONG = "<b>Create a new song entry</b><br/><br/>This fieldset enters a new song into the database";
    const PARAM_SONG_ORIGINAL = 'song-original';
    const PARAM_SONG_SIMILAR = 'song-similar';
    const PARAM_SONG_CHIP_STYLE = 'song-chip-style';
    const PARAM_SONG_SOURCE_URL = 'song-source-url';
    const PARAM_SONG_DOWNLOAD_URL = 'song-download-url';

    private $newSongID;

    public function getNewSongID() {
        return $this->newSongID;
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

        $systemList = SystemEntry::getAll();
        $genreList = GenreEntry::getAll();

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/song.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/song.css'),

			new HTMLElement('fieldset', 'fieldset-create-song',
				new HTMLElement('legend', 'legend-song', self::TITLE),

                new HTMLPathTip($Request, '#gen-tips', self::TIPS_CREATE_SONG),

                new HTMLElement('fieldset', 'fieldset-song-data inline',
                    new HTMLElement('legend', 'legend-song-data', "Song Information:"),

                    new HTMLElement('label', null, "Song Title:<br/>",
                        new HTMLInputField(self::PARAM_SONG_TITLE,
                            new Attributes('placeholder', 'My Song Title'),
                            new RequiredValidation()
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Song Artist(s) [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_SONG_ARTIST,
                            new Attributes('placeholder', 'i.e. "Artist1, Artist2"'),
                            new RequiredValidation()
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Description:<br/>",
                        new HTMLTextAreaField(self::PARAM_SONG_DESCRIPTION,
                            new Attributes('placeholder', 'Enter a song description'),
                            new Attributes('rows', 6, 'cols', 30),
                            new RequiredValidation()
                        )
                    ),

                    "<br/>",
                    new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags')
                ),

                new HTMLElement('fieldset', 'fieldset-song-association inline',
                    new HTMLElement('legend', 'legend-song-association', "Sources:"),

                    new HTMLElement('label', null, "Source URLs [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_SONG_SOURCE_URL,
                            new Attributes('placeholder', 'i.e. "http://coundsoud.cod/smbhurrycastle"'),
                            new Attributes('size', 42)
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "File Download URLs [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_SONG_DOWNLOAD_URL,
                            new Attributes('placeholder', 'i.e. "http://fileserver.com/mirror/smbhurrycastle.mp3, http://fileserver2.com/mirror2/smbhurrycastle.mp3"'),
                            new Attributes('size', 42)
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Remix/Cover of Original [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_SONG_ORIGINAL,
                            new Attributes('placeholder', 'i.e. "SMB Castle Complete, SMB Hurry Castle"'),
                            new Attributes('size', 42),
                            new RequiredValidation()
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Similar Songs [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_SONG_SIMILAR,
                            new Attributes('placeholder', 'i.e. "Thought You Could Castle, You Got Castled"'),
                            new Attributes('size', 42),
                            new RequiredValidation()
                        )
                    )
                ),

                new HTMLPopUpBox(null, HTMLPopUpBox::CLASS_IMPORTANT, 'Free file hosting for chip-tune originals!'),

                new HTMLElement('fieldset', 'fieldset-song-chip-style inline',
                    new HTMLElement('legend', 'legend-song-chip-style', "Chip Style:"),
                    new HTMLSelectField(self::PARAM_SONG_CHIP_STYLE . '[]', DefaultChipStyles::getDefaults() + array(
                            "Not a chip tune" => null,
                        ),
                        new Attributes('multiple', 'multiple'),
                        new Attributes('size', 5),
                        new RequiredValidation()
                    )
                ),

                new HTMLElement('fieldset', 'fieldset-song-systems inline',
                    new HTMLElement('legend', 'legend-song-systems', "Game System:"),
                    new HTMLSelectField(self::PARAM_SONG_SYSTEM . '[]', $systemList,
                        new Attributes('multiple', 'multiple'),
                        new Attributes('size', 7),

                        new RequiredValidation()
                    )
                ),

                new HTMLElement('fieldset', 'fieldset-song-genre inline',
                    new HTMLElement('legend', 'legend-song-genre', "Genre:"),
                    new HTMLSelectField(self::PARAM_SONG_GENRE . '[]', $genreList,
                        new Attributes('multiple', 'multiple'),
                        new Attributes('size', 9),
                        new RequiredValidation()
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

        $title = $Form->validateField($Request, self::PARAM_SONG_TITLE);
        $description = $Form->validateField($Request, self::PARAM_SONG_DESCRIPTION);

        $tags[self::PARAM_SONG_ARTIST] = array(SongTagEntry::TAG_ARTIST, $Form->validateField($Request, self::PARAM_SONG_ARTIST));
        $tags[self::PARAM_SONG_SYSTEM] = array(SongTagEntry::TAG_SYSTEM, $Form->validateField($Request, self::PARAM_SONG_SYSTEM));
        $tags[self::PARAM_SONG_GENRE] = array(SongTagEntry::TAG_GENRE, $Form->validateField($Request, self::PARAM_SONG_GENRE));

        $tags[self::PARAM_SONG_ORIGINAL] = array(SongTagEntry::TAG_ORIGINAL, $Form->validateField($Request, self::PARAM_SONG_ORIGINAL));
        $tags[self::PARAM_SONG_SIMILAR] = array(SongTagEntry::TAG_SIMILAR, $Form->validateField($Request, self::PARAM_SONG_SIMILAR));
        $tags[self::PARAM_SONG_CHIP_STYLE] = array(SongTagEntry::TAG_CHIP_STYLE, $Form->validateField($Request, self::PARAM_SONG_CHIP_STYLE));

        $MatchingSong = SongEntry::table()
            ->select()
            ->where(SongTable::COLUMN_TITLE, $title)
            ->where(SongTable::COLUMN_STATUS, SongEntry::STATUS_PUBLISHED, '&?')
            ->fetch();

        if($MatchingSong)
            throw new \InvalidArgumentException("A published song already has this name. What gives!?");

		$Song = SongEntry::create($Request, $title, $description);
        foreach($tags as $param => $info) {
            list($tagName, $values) = $info;
            if(!is_array($values))
                $values = explode(',', $values);
            foreach($values as $value) {
                if($value) {
                    $Song->addTag($Request, $tagName, $value);
                }
            }
        }

        $Song->addTag($Request, SongTagEntry::TAG_ENTRY_ACCOUNT, $Account->getFingerprint());

        $this->newSongID = $Song->getID();

        return new RedirectResponse(ManageSong::getRequestURL($Song->getID()), "Song created successfully. Redeflecting...", 5);
	}

	// Static

	public static function getRequestURL() {
		return self::FORM_ACTION;
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
		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION2, __CLASS__, IRequest::NAVIGATION_ROUTE | IRequest::MATCH_SESSION_ONLY, "Songs");
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
        $TestAccount = new AccountEntry('test-fp');
        $Session[AccountEntry::SESSION_KEY] = serialize($TestAccount);

        $CreateSong = new CreateSong();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_SONG_TITLE, 'test-song-title');
        $Test->setRequestParameter(self::PARAM_SONG_ARTIST, 'test-song-artist');
        $Test->setRequestParameter(self::PARAM_SONG_SIMILAR, 'test-song-similar');
        $Test->setRequestParameter(self::PARAM_SONG_ORIGINAL, 'test-song-original');
        $Test->setRequestParameter(self::PARAM_SONG_CHIP_STYLE, '8-bit');
        $Test->setRequestParameter(self::PARAM_SONG_DESCRIPTION, 'test-song-description');
        $Test->setRequestParameter(self::PARAM_SONG_GENRE, array('test-song-genre'));
        $Test->setRequestParameter(self::PARAM_SONG_SYSTEM, array('test-song-system'));
        $CreateSong->execute($Test);

//        $id = $CreateSong->getNewSongID();

        SongEntry::table()->delete(SongTable::COLUMN_TITLE, 'test-song-title');
//        SongEntry::delete($Test, $id);

    }
}