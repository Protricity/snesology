<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song\Album;

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
use CPath\Response\Response;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\Register;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\Request\DB\RequestEntry;
use Site\SiteMap;
use Site\Song\Album\DB\AlbumEntry;
use Site\Song\Album\DB\AlbumTable;
use Site\Song\Defaults\DefaultChipStyles;
use Site\Song\Genre\DB\GenreEntry;
use Site\Song\System\DB\SystemEntry;
use Site\Song\Tag\DB\TagEntry;
use Site\Song\Tag\DB\TagTable;

class CreateAlbum implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Create a new Album Entry';

	const FORM_ACTION = '/create/album/';
	const FORM_ACTION2 = '/albums/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'create-album';

    const PARAM_ALBUM_TITLE = 'album-title';
    const PARAM_ALBUM_ARTIST = 'album-artist';
    const PARAM_ALBUM_GENRE = 'album-genre';
    const PARAM_ALBUM_SYSTEM = 'album-system';
    const PARAM_ALBUM_DESCRIPTION = 'album-description';
    const TIPS_CREATE_ALBUM = "<b>Create a new album entry</b><br/><br/>This fieldset enters a new album into the database";
    const PARAM_ALBUM_ORIGINAL = 'album-original';
    const PARAM_ALBUM_SIMILAR = 'album-similar';
    const PARAM_ALBUM_CHIP_STYLE = 'album-chip-style';
    const PARAM_ALBUM_SOURCE_URL = 'album-source-url';
    const PARAM_ALBUM_DOWNLOAD_URL = 'album-download-url';

    private $newAlbumID;

    public function getNewAlbumID() {
        return $this->newAlbumID;
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

        if(!AccountEntry::hasActiveSession($SessionRequest))
            return new Response("Login required for editing");
        $Account = AccountEntry::loadFromSession($SessionRequest);

        $systemList = SystemEntry::getAll();
        $genreList = GenreEntry::getAll();

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/album.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/album.css'),

			new HTMLElement('fieldset', 'fieldset-create-album',
				new HTMLElement('legend', 'legend-album', self::TITLE),

                new HTMLPathTip($Request, '#gen-tips', self::TIPS_CREATE_ALBUM),

                new HTMLElement('fieldset', 'fieldset-album-data inline',
                    new HTMLElement('legend', 'legend-album-data', "Album Information:"),

                    new HTMLElement('label', null, "Album Title:<br/>",
                        new HTMLInputField(self::PARAM_ALBUM_TITLE,
                            new Attributes('placeholder', 'My Album Title'),
                            new RequiredValidation()
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Album Artist(s) [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_ALBUM_ARTIST,
                            new Attributes('placeholder', 'i.e. "Artist1, Artist2"'),
                            new RequiredValidation()
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Description:<br/>",
                        new HTMLTextAreaField(self::PARAM_ALBUM_DESCRIPTION,
                            new Attributes('placeholder', 'Enter an album description'),
                            new Attributes('rows', 6, 'cols', 30),
                            new RequiredValidation()
                        )
                    ),

                    "<br/>",
                    new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags')
                ),

                new HTMLElement('fieldset', 'fieldset-album-association inline',
                    new HTMLElement('legend', 'legend-album-association', "Sources:"),

                    new HTMLElement('label', null, "Source URLs [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_ALBUM_SOURCE_URL,
                            new Attributes('placeholder', 'i.e. "http://coundsoud.cod/albums/smbalbum"'),
                            new Attributes('size', 42)
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "File Download URLs [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_ALBUM_DOWNLOAD_URL,
                            new Attributes('placeholder', 'i.e. "http://fileserver.com/mirror/smbalbum/"'),
                            new Attributes('size', 42)
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Remix/Cover of Original [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_ALBUM_ORIGINAL,
                            new Attributes('placeholder', 'i.e. "Super Mario Brothers"'),
                            new Attributes('size', 42),
                            new RequiredValidation()
                        )
                    ),

                    "<br/><br/>",
                    new HTMLElement('label', null, "Similar Albums [comma delimited]:<br/>",
                        new HTMLInputField(self::PARAM_ALBUM_SIMILAR,
                            new Attributes('placeholder', 'i.e. "Castle Hassle"'),
                            new Attributes('size', 42),
                            new RequiredValidation()
                        )
                    )
                ),

                new HTMLPopUpBox(null, HTMLPopUpBox::CLASS_IMPORTANT, 'Free file hosting for chip-tune albums!'),

                new HTMLElement('fieldset', 'fieldset-album-chip-style inline',
                    new HTMLElement('legend', 'legend-album-chip-style', "Chip Style:"),
                    new HTMLSelectField(self::PARAM_ALBUM_CHIP_STYLE . '[]', DefaultChipStyles::getDefaults() + array(
                            "Not a chip tune album" => null,
                        ),
                        new Attributes('multiple', 'multiple'),
                        new Attributes('size', 5),
                        new RequiredValidation()
                    )
                ),

                new HTMLElement('fieldset', 'fieldset-album-systems inline',
                    new HTMLElement('legend', 'legend-album-systems', "Game System:"),
                    new HTMLSelectField(self::PARAM_ALBUM_SYSTEM . '[]', $systemList,
                        new Attributes('multiple', 'multiple'),
                        new Attributes('size', 7),

                        new RequiredValidation()
                    )
                ),

                new HTMLElement('fieldset', 'fieldset-album-genre inline',
                    new HTMLElement('legend', 'legend-album-genre', "Genre:"),
                    new HTMLSelectField(self::PARAM_ALBUM_GENRE . '[]', $genreList,
                        new Attributes('multiple', 'multiple'),
                        new Attributes('size', 9),
                        new RequiredValidation()
                    )
                ),

				"<br/>",
				new HTMLButton('submit', 'Create Album Entry', 'submit')
			),
			"<br/>"
		);

		if(!$Request instanceof IFormRequest)
			return $Form;

        $Form->setFormValues($Request);

        $title = $Form->validateField($Request, self::PARAM_ALBUM_TITLE);
        $description = $Form->validateField($Request, self::PARAM_ALBUM_DESCRIPTION);

        $tags[self::PARAM_ALBUM_ARTIST] = array(TagEntry::TAG_ARTIST, $Form->validateField($Request, self::PARAM_ALBUM_ARTIST));
        $tags[self::PARAM_ALBUM_SYSTEM] = array(TagEntry::TAG_SYSTEM, $Form->validateField($Request, self::PARAM_ALBUM_SYSTEM));
        $tags[self::PARAM_ALBUM_GENRE] = array(TagEntry::TAG_GENRE, $Form->validateField($Request, self::PARAM_ALBUM_GENRE));

        $tags[self::PARAM_ALBUM_ORIGINAL] = array(TagEntry::TAG_ORIGINAL, $Form->validateField($Request, self::PARAM_ALBUM_ORIGINAL));
        $tags[self::PARAM_ALBUM_SIMILAR] = array(TagEntry::TAG_SIMILAR, $Form->validateField($Request, self::PARAM_ALBUM_SIMILAR));
        $tags[self::PARAM_ALBUM_CHIP_STYLE] = array(TagEntry::TAG_CHIP_STYLE, $Form->validateField($Request, self::PARAM_ALBUM_CHIP_STYLE));

        $MatchingAlbum = AlbumEntry::table()
            ->select()
            ->where(AlbumTable::COLUMN_TITLE, $title)
            ->where(AlbumTable::COLUMN_STATUS, AlbumEntry::STATUS_PUBLISHED, '&?')
            ->fetch();

        if($MatchingAlbum)
            throw new \InvalidArgumentException("A published album already has this name. What gives!?");

		$Album = AlbumEntry::create($Request, $Account, $title, $description);
        foreach($tags as $param => $info) {
            list($tagName, $values) = $info;
            if(!is_array($values))
                $values = explode(',', $values);
            foreach($values as $value) {
                if($value) {
                    $Album->addTag($Request, $tagName, $value);
                }
            }
        }

        $Album->addTag($Request, TagEntry::TAG_ENTRY_ACCOUNT, $Account->getFingerprint());

        $this->newAlbumID = $Album->getID();

        RequestEntry::createFromRequest($Request, $Account);

        return new RedirectResponse(ManageAlbum::getRequestURL($Album->getID()), "Album created successfully. Backspacing...", 5);
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
		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION2, __CLASS__, IRequest::NAVIGATION_ROUTE | IRequest::MATCH_SESSION_ONLY, "Albums");
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

        AlbumEntry::table()->delete(AlbumTable::COLUMN_TITLE, 'test-album-title');

        $CreateAlbum = new CreateAlbum();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(self::PARAM_ALBUM_TITLE, 'test-album-title');
        $Test->setRequestParameter(self::PARAM_ALBUM_ARTIST, 'test-album-artist');
        $Test->setRequestParameter(self::PARAM_ALBUM_SIMILAR, 'test-album-similar');
        $Test->setRequestParameter(self::PARAM_ALBUM_ORIGINAL, 'test-album-original');
        $Test->setRequestParameter(self::PARAM_ALBUM_CHIP_STYLE, '8-bit');
        $Test->setRequestParameter(self::PARAM_ALBUM_DESCRIPTION, 'test-album-description');
        $Test->setRequestParameter(self::PARAM_ALBUM_GENRE, array('test-album-genre'));
        $Test->setRequestParameter(self::PARAM_ALBUM_SYSTEM, array('test-album-system'));
        $CreateAlbum->execute($Test);

        $id = $CreateAlbum->getNewAlbumID();

        AlbumEntry::delete($Test, $id);

    }
}