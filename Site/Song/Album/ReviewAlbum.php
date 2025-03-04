<?php
/**
 * Reviewd by PhpStorm.
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
use Site\Account\DB\AccountEntry;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\Render\PopUpBox\HTMLPopUpBox;
use Site\Request\DB\RequestEntry;
use Site\SiteMap;
use Site\Song\Album\DB\AlbumEntry;
use Site\Song\Review\DB\ReviewEntry;
use Site\Song\Review\HTML\HTMLSourceReview;
use Site\Song\Review\ReviewTag\DB\ReviewTagEntry;


class ReviewAlbum implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Review na Album';

	const FORM_ACTION = '/review/album/:id';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'review-album';

    const PARAM_ALBUM_ID = 'id';
    const PARAM_ALBUM_REVIEW_TITLE = 'title';
    const PARAM_ALBUM_REVIEW = 'review';
    const PARAM_SUBMIT = 'submit';
    const PARAM_ALBUM_STATUS = 'status';
    const PARAM_REVIEW_REMOVE_TAG = 'review-remove-tag';
    const PARAM_REVIEW_TAG_NAME = 'review-tag-name';
    const PARAM_REVIEW_TAG_VALUE = 'review-tag-value';

    const TIP_REVIEW = '<b>Review an album</b><br /><br />Create an album review';

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
        $ReviewEntry = ReviewEntry::fetch($Album->getID(), $Account->getFingerprint());

        $Preview = new HTMLSourceReview($ReviewEntry, $Account);
        $tagList = ReviewTagEntry::$TagDefaults;
        $oldTags = $ReviewEntry->getTagList();

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/review.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/review.css'),

            new HTMLElement('fieldset', 'fieldset-review-album inline',
                new HTMLElement('legend', 'legend-album', "Review '" . $Album->getTitle() . "'"),

                new HTMLPathTip($Request, '#tip-select', self::TIP_REVIEW),

                new HTMLElement('label', null, "Album Title:<br/>",
                    new HTMLInputField(self::PARAM_ALBUM_REVIEW_TITLE, $ReviewEntry ? $ReviewEntry->getReviewTitle() : null,
                        new Attributes('placeholder', 'Review Title'),
                        new Attributes('size', 78)
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Review:<br/>",
                    new HTMLTextAreaField(self::PARAM_ALBUM_REVIEW, $ReviewEntry ? $ReviewEntry->getReview() : null,
                        new Attributes('placeholder', 'Enter a album review'),
                        new Attributes('rows', 15, 'cols', 80),
                        new RequiredValidation()
                    )
                ),

                "<br/>",
                new HTMLPopUpBox('&#60;' . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;', HTMLPopUpBox::CLASS_INFO, 'Allowed Tags'),

                "<br/><br/>",
                new HTMLElement('label', null, "Status:<br/>",
                    $SelectStatus = new HTMLSelectField(self::PARAM_ALBUM_STATUS . '[]', ReviewEntry::$StatusOptions,
                        new Attributes('multiple', 'multiple'),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                $ReviewEntry
                    ? new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
                    : new HTMLButton(self::PARAM_SUBMIT, 'Create', 'create')
            ),

            new HTMLElement('fieldset', 'fieldset-view-album-review-preview inline',
                new HTMLElement('legend', 'legend-view-album-review-preview', "Review Preview or is it Preview Review"),

                $Preview
            ),

            ($ReviewEntry->hasFlags(ReviewEntry::STATUS_PUBLISHED)
                ? null
                : new HTMLElement('fieldset', 'fieldset-manage-album-publish inline',
                    new HTMLElement('legend', 'legend-album-publish', "Publish!"),

                    "Nailed it down? <br/> All set? <br/><br/>",
                    new HTMLButton(self::PARAM_SUBMIT, 'Publish Review', 'publish')
                )
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
                    new HTMLInputField(self::PARAM_REVIEW_TAG_VALUE, null, null, 'input tag-type tag-type-' . ReviewTagEntry::TAG_TYPE_STRING),

                    new HTMLInputField(self::PARAM_REVIEW_TAG_VALUE, 0, 'hidden', 'input tag-type tag-type-' . ReviewTagEntry::TAG_TYPE_BOOLEAN),
                    new HTMLCheckBoxField(self::PARAM_REVIEW_TAG_VALUE, false, 1, 'input tag-type tag-type-' . ReviewTagEntry::TAG_TYPE_BOOLEAN,
                        new StyleAttributes('display', 'none')
                    ),

                    new HTMLInputField(self::PARAM_REVIEW_TAG_VALUE, null, null, 'input tag-type tag-type-' . ReviewTagEntry::TAG_TYPE_5STAR,
                        new Attributes('size', 1)
                    ),
                    new HTMLRangeInputField(self::PARAM_REVIEW_TAG_VALUE, 0, 0, 5, 0.1, 'input tag-type tag-type-' . ReviewTagEntry::TAG_TYPE_5STAR,
                        new Attributes('oninput', 'var i=jQuery(this); i.siblings("input").val(i.val())'),
                        new StyleAttributes('display', 'none')
                    )

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

            "<br/><br/>",

            new HTMLElement('fieldset', 'fieldset-view-album-info inline',
                new HTMLElement('legend', 'legend-view-album-info', "Album Information"),

                new MapRenderer($Album)
            )
		);

        if($ReviewEntry)
            foreach(AlbumEntry::$StatusOptions as $desc => $flag)
                if($ReviewEntry->hasFlags($flag))
                    $SelectStatus->addOption($flag, $desc, true);

        foreach($oldTags as $name => $value) {
            $title = array_search($name, ReviewTagEntry::$TagDefaults) ?: $name;
            $SelectRemoveTag->addOption($name.';'.$value, "{$title} - {$value}");
        }

		if(!$Request instanceof IFormRequest)
			return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        RequestEntry::createFromRequest($Request, $Account);

        switch($submit) {
            case 'add-review-tag':
                $tagName = $Form->validateField($Request, self::PARAM_REVIEW_TAG_NAME);
                $tagValue = $Form->validateField($Request, self::PARAM_REVIEW_TAG_VALUE);
                $ReviewEntry->addTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ReviewAlbum::getRequestURL($Album->getID()), "Added Review Tag successfully. There's no going. You just go...", 5);

            case 'remove-review-tag':
                list($tagName, $tagValue) = explode(';', $Form->validateField($Request, self::PARAM_REVIEW_REMOVE_TAG), 2);
                $ReviewEntry->removeTag($Request, $tagName, $tagValue);
                return new RedirectResponse(ReviewAlbum::getRequestURL($Album->getID()), "Removed tag successfully. Re(move)directing...", 5);

            case 'publish':
                $status = $ReviewEntry->getStatusFlags();
                $status |= ReviewEntry::STATUS_PUBLISHED;
                $ReviewEntry->update($Request, null, null, $status);
                return new RedirectResponse(ReviewAlbum::getRequestURL($Album->getID()), "Published Album Review. omg omg omg...", 5);

            case 'update':
                $status = $Form->validateField($Request, self::PARAM_ALBUM_STATUS);
                $status = array_sum($status);
                $review = $Form->validateField($Request, self::PARAM_ALBUM_REVIEW);
                $reviewTitle = $Form->validateField($Request, self::PARAM_ALBUM_REVIEW_TITLE);
                $ReviewEntry->update($Request, $review, $reviewTitle, $status);
                return new RedirectResponse(ReviewAlbum::getRequestURL($Album->getID()), "Updated Album Review. Re(view)directing...", 5);

            case 'create':
                $status = $Form->validateField($Request, self::PARAM_ALBUM_STATUS);
                $status = array_sum($status);
                $review = $Form->validateField($Request, self::PARAM_ALBUM_REVIEW);
                $reviewTitle = $Form->validateField($Request, self::PARAM_ALBUM_REVIEW_TITLE);
                ReviewEntry::addToSource($Request, $Album->getID(), 'album', $Account->getFingerprint(), $review, $reviewTitle, $status);
                return new RedirectResponse(ReviewAlbum::getRequestURL($Album->getID()), "Added Album Review. Re(view)creating...", 5);

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
}