<?php
/**
 * Managed by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song\Album;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Attribute\StyleAttributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
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
use Site\Song\Album\DB\AlbumEntry;
use Site\Song\Album\DB\AlbumTable;
use Site\Song\Review\HTML\HTMLAlbumReviewsTable;
use Site\Song\Tag\DB\TagEntry;

class ManageAlbum implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Manage an Album';

	const FORM_ACTION = '/manage/album/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'manage-album';

    const PARAM_ALBUM_ID = 'id';
    const PARAM_ALBUM_TITLE = 'title';
    const PARAM_ALBUM_DESCRIPTION = 'description';

    const PARAM_ALBUM_TAG_NAME = 'tag-name';
    const PARAM_ALBUM_TAG_VALUE = 'tag-value';
    const PARAM_SUBMIT = 'submit';
    const PARAM_ALBUM_REMOVE_TAG = 'remove-tag';

    private $id;

    public function __construct($albumID) {
        $this->id = $albumID;
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

        $Album = AlbumEntry::get($this->id);

        $tagList = TagEntry::$TagDefaults;

        $ReviewTable = new HTMLAlbumReviewsTable($Request, $Album->getID());

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/album.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/album.css'),

            new HTMLElement('fieldset', 'fieldset-manage-album inline',
                new HTMLElement('legend', 'legend-album', self::TITLE),

                new HTMLElement('label', null, "Album Title:<br/>",
                    new HTMLInputField(self::PARAM_ALBUM_TITLE, $Album->getTitle(),
                        new Attributes('placeholder', 'Edit Album Title'),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Description:<br/>",
                    new HTMLTextAreaField(self::PARAM_ALBUM_DESCRIPTION, $Album->getDescription(),
                        new Attributes('placeholder', 'Enter a album description'),
                        new Attributes('rows', 10, 'cols', 40),
                        new RequiredValidation()
                    )
                ),

                "<br/>",
                new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags'),

                "<br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
            ),

            ($Album->hasFlags(AlbumEntry::STATUS_PUBLISHED)
                ? null
                : new HTMLElement('fieldset', 'fieldset-manage-album-publish inline',
                    new HTMLElement('legend', 'legend-album-publish', "Publish!"),

                    "Tags in place? <br/> All ready to go? <br/><br/>",
                    new HTMLButton(self::PARAM_SUBMIT, 'Publish "' . $Album->getTitle() . '"', 'publish')
                )
            ),

            new HTMLElement('fieldset', 'fieldset-manage-album-tags-add inline',
                new HTMLElement('legend', 'legend-album-tags-add', "Add Album Tag"),

                new HTMLElement('label', null, "Tag Name<br/>",
                    new HTMLSelectField(self::PARAM_ALBUM_TAG_NAME, $tagList
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Tag Value<br/>",
                    new HTMLInputField(self::PARAM_ALBUM_TAG_VALUE
                    )
                ),

//                new HTMLCheckBoxField(self::PARAM_ALBUM_TAG_VALUE,
//                    new Attributes('disabled', 'disabled')
//                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Add Tag', 'add-album-tag')
            ),

            new HTMLElement('fieldset', 'fieldset-manage-album-tags-remove inline',
                new HTMLElement('legend', 'legend-album-tags-remove', "Remove Album Tag"),

                new HTMLElement('label', null, "Tag Name<br/>",
                    $SelectRemoveTag = new HTMLSelectField(self::PARAM_ALBUM_REMOVE_TAG,
                        array("Select a tag to remove" => null),
                        new StyleAttributes('width', '15em')
                    )
                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Remove Tag', 'remove-album-tag')
            ),

            new HTMLElement('fieldset', 'fieldset-album-info inline',
                new HTMLElement('legend', 'legend-album-info', "Album Information"),

                new MapRenderer($Album)
            ),

            new HTMLElement('fieldset', 'fieldset-manage-album-reviews inline',
                new HTMLElement('legend', 'legend-album-reviews', "Album Reviews"),

                $ReviewTable,

                new HTMLAnchor(ReviewAlbum::getRequestURL($Album->getID()), "Add a review")
            )
		);


//        foreach($oldSystems as $system)
//            $SelectSystem->addOption($system, $system, true);

//        foreach($oldGenres as $genre)
//            $SelectGenre->addOption($genre, $genre, true);

        foreach($Album->getTagList() as $tag) {
            list($tagName, $tagValue) = $tag;
            $title = array_search($tagName, TagEntry::$TagDefaults) ?: $tagName;
            $SelectRemoveTag->addOption($tagName.':'.$tagValue, "{$title} - {$tagValue}");
        }

		if(!$Request instanceof IFormRequest)
			return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        RequestEntry::createFromRequest($Request, $Account);

        switch($submit) {
            case 'add-album-tag':
                $tagName = $Form->validateField($Request, self::PARAM_ALBUM_TAG_NAME);
                $tagValue = $Form->validateField($Request, self::PARAM_ALBUM_TAG_VALUE);
                $Album->addTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ManageAlbum::getRequestURL($Album->getID()), "Updated album successfully. Misdirecting...", 5);

            case 'remove-album-tag':
                list($tagName, $tagValue) = explode(':', $Form->validateField($Request, self::PARAM_ALBUM_REMOVE_TAG), 2);
                $Album->removeTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ManageAlbum::getRequestURL($Album->getID()), "Removed tag successfully. Reexploding...", 5);

            case 'publish':
                $Album->publish($Request, $Form);
                return new RedirectResponse(ManageAlbum::getRequestURL($Album->getID()), "Album published successfully. You rock", 5);

            case 'update':
                $newTitle = $Form->validateField($Request, self::PARAM_ALBUM_TITLE);
                $newDescription = $Form->validateField($Request, self::PARAM_ALBUM_DESCRIPTION);

                if($newTitle !== $Album->getTitle()
                 || $newDescription !== $Album->getDescription()) {
                    $Album->update($Request, $newTitle, $newDescription);
                }

                return new RedirectResponse(ManageAlbum::getRequestURL($Album->getID()), "Updated album successfully. Redissecting...", 5);

            default:
                throw new \InvalidArgumentException("Invalid submit: " . $submit);
        }

	}

	// Static

	public static function getRequestURL($albumID) {
        return str_replace(':' . self::PARAM_ALBUM_ID, $albumID, self::FORM_ACTION);
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
		return new ExecutableRenderer(new static($Request[self::PARAM_ALBUM_ID]), true);
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

        AlbumEntry::table()->delete(AlbumTable::COLUMN_TITLE, 'test-album-title');

        $CreateAlbum = new CreateAlbum();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_TITLE, 'test-album-title');
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_ARTIST, 'test-album-artist');
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_SIMILAR, 'test-album-similar');
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_ORIGINAL, 'test-album-original');
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_CHIP_STYLE, '8-bit');
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_DESCRIPTION, 'test-album-description');
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_GENRE, array('test-album-genre'));
        $Test->setRequestParameter(CreateAlbum::PARAM_ALBUM_SYSTEM, array('test-album-system'));
        $CreateAlbum->execute($Test);

        $id = $CreateAlbum->getNewAlbumID();

        $ManageAlbum = new ManageAlbum($id);

        $Test->clearRequestParameters();
        $Test->setRequestParameter(ManageAlbum::PARAM_ALBUM_TITLE, 'test-album-title2');
        $Test->setRequestParameter(ManageAlbum::PARAM_ALBUM_DESCRIPTION, 'test-album-description2');
        $Test->setRequestParameter(ManageAlbum::PARAM_SUBMIT, 'update');
        $ManageAlbum->execute($Test);

        AlbumEntry::delete($Test, $id);
    }
}