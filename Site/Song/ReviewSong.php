<?php
/**
 * Reviewd by PhpStorm.
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
use CPath\Render\HTML\Element\Form\HTMLRangeInputField;
use CPath\Render\HTML\Element\Form\HTMLSelectField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLHeaderScript;
use CPath\Render\HTML\Header\HTMLHeaderStyleSheet;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\HTML\IHTMLContainer;
use CPath\Render\Map\MapRenderer;
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
use Site\Account\Guest\GuestAccount;
use Site\Account\Guest\TestAccount;
use Site\Account\Register;
use Site\Account\Session\DB\SessionEntry;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\Relay\HTML\HTMLRelayChat;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\Request\DB\RequestEntry;
use Site\SiteMap;
use Site\Song\DB\SongEntry;
use Site\Song\DB\SongTable;
use Site\Song\Review\DB\ReviewEntry;
use Site\Song\Review\DB\ReviewTable;
use Site\Song\Review\HTML\HTMLSourceReview;
use Site\Song\Review\ReviewTag\DB\ReviewTagEntry;
use Site\Song\Tag\DB\TagEntry;


class ReviewSong implements IExecutable, IBuildable, IRoutable, ITestable
{
	const TITLE = 'Review a Song';

	const FORM_ACTION = '/review/song/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'review-song';

    const PARAM_SONG_ID = 'id';
    const PARAM_SONG_REVIEW_TITLE = 'title';
    const PARAM_SONG_REVIEW = 'review';
    const PARAM_SONG_STATUS = 'status';
    const PARAM_REVIEW_REMOVE_TAG = 'review-remove-tag';
    const PARAM_REVIEW_TAG_NAME = 'review-tag-name';
    const PARAM_REVIEW_TAG_VALUE = 'review-tag-value';
    const PARAM_SONG_REVIEW_5STAR = 'review-5star';

    const PARAM_SUBMIT = 'submit';

    const TIP_REVIEW = '<b>Review a song</b><br /><br />Create a song review';
    const PARAM_SONG_REVIEW_RECOMMEND = 'review-recommend';
    const PARAM_SONG_REVIEW_UNRECOMMEND = 'review-unrecommend';
    private $id;

    public function __construct($songID) {
        $this->id = $songID;
    }

    /**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws \Exception
	 * @return Response the execution response
	 */
	function execute(IRequest $Request) {
		$SessionRequest = $Request;
		if (!$SessionRequest instanceof ISessionRequest)
			throw new \Exception("Session required");

        $Account = AccountEntry::loadFromSession($SessionRequest);
        $Song = SongEntry::get($this->id);
        $ReviewEntry = ReviewEntry::fetch($Song->getID(), $Account->getFingerprint());

        $Preview = null;
        $oldTags = array();
        if($ReviewEntry) {
            $oldTags = $ReviewEntry->getTagList();
            $Preview = new HTMLSourceReview($ReviewEntry, $Account);
        }
        $tagList = ReviewTagEntry::$TagDefaults;
        $old5StarReview = isset($oldTags[ReviewTagEntry::TAG_RATING]) ? $oldTags[ReviewTagEntry::TAG_RATING] : '3.0';
        $recommended = isset($oldTags[ReviewTagEntry::TAG_RECOMMENDED]) ? $oldTags[ReviewTagEntry::TAG_RECOMMENDED] : false;

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/review.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/review.css'),

            new HTMLElement('fieldset', 'fieldset-review-song inline',
                new HTMLElement('legend', 'legend-song', "Review '" . $Song->getTitle() . "'"),

                new HTMLPathTip($Request, '#tip-select', self::TIP_REVIEW),

                new HTMLElement('label', null, "Song Title:<br/>",
                    new HTMLInputField(self::PARAM_SONG_REVIEW_TITLE, $ReviewEntry ? $ReviewEntry->getReviewTitle() : null,
                        new Attributes('placeholder', 'Review Title'),
                        new Attributes('size', 78)
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Review:<br/>",
                    new HTMLTextAreaField(self::PARAM_SONG_REVIEW, $ReviewEntry ? $ReviewEntry->getReview() : null,
                        new Attributes('placeholder', 'Enter a song review'),
                        new Attributes('rows', 15, 'cols', 80),
                        new RequiredValidation()
                    )
                ),

                "<br/>",
                new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags'),

                "<br/><br/>",
                new HTMLElement('label', null, "Status:<br/>",
                    $SelectStatus = new HTMLSelectField(self::PARAM_SONG_STATUS . '[]', ReviewEntry::$StatusOptions,
                        new Attributes('multiple', 'multiple')
//                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                $ReviewEntry
                    ? new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
                    : new HTMLButton(self::PARAM_SUBMIT, 'Create', 'create')
            ),

            ($ReviewEntry && $ReviewEntry->hasFlags(ReviewEntry::STATUS_PUBLISHED)
                ? null
                : new HTMLElement('fieldset', 'fieldset-manage-review-publish float',
                    new HTMLElement('legend', 'legend-review-publish', "Publish!"),

                    "Nailed it down? <br/> All set? <br/><br/>",
                    new HTMLButton(self::PARAM_SUBMIT, 'Publish Review', 'publish')
                )
            ),

            new HTMLElement('fieldset', 'fieldset-view-song-review-preview inline',
                new HTMLElement('legend', 'legend-view-song-review-preview', "Review Preview or is it Preview Review"),

                $Preview
            ),


            new HTMLElement('fieldset', 'fieldset-review-recommend inline',
                new HTMLElement('legend', 'legend-review-recommend', "Recommend this song"),

                !$recommended
                    ? new HTMLButton(self::PARAM_SUBMIT, 'Recommend', self::PARAM_SONG_REVIEW_RECOMMEND)
                    : new HTMLButton(self::PARAM_SUBMIT, 'Remove Recommendation', self::PARAM_SONG_REVIEW_UNRECOMMEND)
            ),

            new HTMLElement('fieldset', 'fieldset-review-5star inline',
                new HTMLElement('legend', 'legend-view-review-5star', "5 Star Rating"),

                new HTMLInputField(self::PARAM_SONG_REVIEW_5STAR, $old5StarReview),
                "<br/>",
                new HTMLRangeInputField(self::PARAM_SONG_REVIEW_5STAR . '_range', $old5StarReview, '0.0', '5.0', '0.1',
                    new Attributes('oninput', 'var i=jQuery(this); i.siblings("input").val(i.val())')
                ),
                "<br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Submit Score', self::PARAM_SONG_REVIEW_5STAR)
            ),

            "<br/><br/>",

            new HTMLElement('fieldset', 'fieldset-manage-review-tags-add inline',
                new HTMLElement('legend', 'legend-review-tags-add', "Add Review Tag"),

                new HTMLElement('label', null, "Tag Name<br/>",
                    new HTMLSelectField(self::PARAM_REVIEW_TAG_NAME, $tagList
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Tag Value<br/>",
                    new HTMLInputField(self::PARAM_REVIEW_TAG_VALUE, null, null, 'input tag-type tag-type-' . ReviewTagEntry::TAG_TYPE_STRING)
                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Add Review Tag', 'add-review-tag')
            ),

            new HTMLElement('fieldset', 'fieldset-manage-review-tags-remove inline',
                new HTMLElement('legend', 'legend-review-tags-remove', "Remove Review Tag"),

                new HTMLElement('label', null, "Tag Name<br/>",
                    $SelectRemoveTag = new HTMLSelectField(self::PARAM_REVIEW_REMOVE_TAG,
                        array("Select a tag to remove" => null),
                        new StyleAttributes('width', '15em')
                    )
                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Remove Tag', 'remove-review-tag')
            ),

            new HTMLElement('fieldset', 'fieldset-view-song-info float',
                new HTMLElement('legend', 'legend-view-song-info', "Song Information"),

                new MapRenderer($Song)
            )
		);

        if($ReviewEntry)
            foreach(ReviewEntry::$StatusOptions as $desc => $flag)
                if($ReviewEntry->hasFlags($flag))
                    $SelectStatus->addOption($flag, $desc, true);

        foreach($oldTags as $name => $value) {
            $title = array_search($name, ReviewTagEntry::$TagDefaults) ?: $name;
            $SelectRemoveTag->addOption($name.';'.$value, "{$title} - {$value}");
        }

        $Form->addContent(new HTMLRelayChat($Request, 'public-chat-song-reviews'), IHTMLContainer::KEY_RENDER_CONTENT_AFTER);

		if(!$Request instanceof IFormRequest)
			return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        RequestEntry::createFromRequest($Request, $Account);

        $CreateResponse = new Response("Error", false);
        if(!$ReviewEntry) {
            $status = $Form->validateField($Request, self::PARAM_SONG_STATUS) ?: array();
            $status = array_sum($status);
            $review = $Form->validateField($Request, self::PARAM_SONG_REVIEW);
            $reviewTitle = $Form->validateField($Request, self::PARAM_SONG_REVIEW_TITLE);
            $reviewID = ReviewEntry::addToSource($Request, $Song->getID(), 'song', $Account->getFingerprint(), $review, $reviewTitle, $status);
            $CreateResponse = new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Added Song Review. Re(view)creating...", 5);
            $CreateResponse->setData('id', $reviewID);
            $ReviewEntry = ReviewEntry::fetch($Song->getID(), $Account->getFingerprint());
        }

        switch($submit) {
            case 'add-review-tag':
                $tagName = $Form->validateField($Request, self::PARAM_REVIEW_TAG_NAME);
                $tagValue = $Form->validateField($Request, self::PARAM_REVIEW_TAG_VALUE);
                $ReviewEntry->addTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Added Review Tag successfully. There's no going. You just go...", 5);

            case 'remove-review-tag':
                list($tagName, $tagValue) = explode(';', $Form->validateField($Request, self::PARAM_REVIEW_REMOVE_TAG), 2);
                $ReviewEntry->removeTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Removed tag successfully. Re(move)directing...", 5);

            case 'publish':
                $status = $ReviewEntry->getStatusFlags();
                $status |= ReviewEntry::STATUS_PUBLISHED;
                $ReviewEntry->update($Request, null, null, $status);
                return new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Published Song Review. omg omg omg...", 5);

            case self::PARAM_SONG_REVIEW_5STAR:
                $tagName = ReviewTagEntry::TAG_RATING;
                $tagValue = $Form->validateField($Request, self::PARAM_SONG_REVIEW_5STAR);

                $ReviewEntry->removeAllTags($Request, $tagName);
                $ReviewEntry->addTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Added Scored Rating successfully. That'll show em...", 5);

            case self::PARAM_SONG_REVIEW_UNRECOMMEND:
            case self::PARAM_SONG_REVIEW_RECOMMEND:
                $tagName = ReviewTagEntry::TAG_RECOMMENDED;
                $tagValue = $submit === self::PARAM_SONG_REVIEW_RECOMMEND;

                $ReviewEntry->removeAllTags($Request, $tagName);
                $ReviewEntry->addTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Updated recommendation successfully. There's no going. You just go...", 5);

            case 'create':
//                $status = $Form->validateField($Request, self::PARAM_SONG_STATUS);
//                $status = array_sum($status);
//                $review = $Form->validateField($Request, self::PARAM_SONG_REVIEW);
//                $reviewTitle = $Form->validateField($Request, self::PARAM_SONG_REVIEW_TITLE);
//                $reviewID = ReviewEntry::addToSource($Request, $Song->getID(), 'song', $Account->getFingerprint(), $review, $reviewTitle, $status);
//                $Response = new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Added Song Review. Re(view)creating...", 5);
//                $Response->setData('id', $reviewID);
                return $CreateResponse;

            case 'update':
                $status = $Form->validateField($Request, self::PARAM_SONG_STATUS) ?: array();
                $status = array_sum($status);
                $review = $Form->validateField($Request, self::PARAM_SONG_REVIEW);
                $reviewTitle = $Form->validateField($Request, self::PARAM_SONG_REVIEW_TITLE);
                $ReviewEntry->update($Request, $review, $reviewTitle, $status);
                return new RedirectResponse(ReviewSong::getRequestURL($Song->getID()), "Updated Song Review. Re(view)directing...", 5);


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
        SessionEntry::create($Test, TestAccount::PGP_FINGERPRINT);

        SongEntry::table()->delete(SongTable::COLUMN_TITLE, 'test-review-title');

        $CreateSong = new CreateSong();

        $Test->clearRequestParameters();
        $Test->setRequestParameter(CreateSong::PARAM_SONG_TITLE, 'test-review-title');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_ARTIST, 'test-review-artist');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_SIMILAR, 'test-review-similar');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_ORIGINAL, 'test-review-original');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_CHIP_STYLE, '8-bit');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_DESCRIPTION, 'test-review-description');
        $Test->setRequestParameter(CreateSong::PARAM_SONG_GENRE, array('test-review-genre'));
        $Test->setRequestParameter(CreateSong::PARAM_SONG_SYSTEM, array('test-review-system'));
        $Response = $CreateSong->execute($Test);

        $id = $Response->getData('id');
        $Test->assert(is_string($id));


        $ReviewSong = new ReviewSong($id);

        $Test->clearRequestParameters();

        $Test->setRequestParameter(ReviewSong::PARAM_SUBMIT, 'create');
        $Test->setRequestParameter(ReviewSong::PARAM_SONG_STATUS, array(ReviewEntry::STATUS_WRITE_UP));
        $Test->setRequestParameter(ReviewSong::PARAM_SONG_REVIEW, 'test-review-content');
        $Test->setRequestParameter(ReviewSong::PARAM_SONG_REVIEW_TITLE, 'test-review-title');

        $Response = $ReviewSong->execute($Test);
        $id = $Response->getData('id');

        $Test->clearRequestParameters();

        $Test->setRequestParameter(ReviewSong::PARAM_SUBMIT, 'update');
        $Test->setRequestParameter(ReviewSong::PARAM_SONG_STATUS, array(ReviewEntry::STATUS_CRITIQUE));
        $Test->setRequestParameter(ReviewSong::PARAM_SONG_REVIEW, 'test-review-content2');
        $Test->setRequestParameter(ReviewSong::PARAM_SONG_REVIEW_TITLE, 'test-review-title2');

        ReviewEntry::delete($Test, $id, TestAccount::PGP_FINGERPRINT);
        SongEntry::delete($Test, $id);
    }
}