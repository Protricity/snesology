<?php
/**
 * Managed by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Song\Artist;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
use CPath\Render\HTML\Element\HTMLAnchor;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLMetaTag;
use CPath\Render\Map\MapRenderer;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Validation\EmailValidation;
use CPath\Request\Validation\RequiredValidation;
use CPath\Request\Validation\URLValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\SiteMap;
use Site\Song\Artist\DB\ArtistEntry;

class ManageArtist implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Manage Artist Information';

    const FORM_ACTION = '/manage/artist/:name';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'manage-artist';

    const PARAM_ARTIST_NAME = 'name';
    const PARAM_ARTIST_DESCRIPTION = 'description';
    const PARAM_ARTIST_URL = 'url';
    const PARAM_SUBMIT = 'submit';
    const PARAM_ARTIST_STATUS = 'status';
    const TIP_MANAGE_ARTIST = '<b>Edit Artist Information</b><br /><br />Add an artist bio or description and publish url';

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
        $OldEntry = ArtistEntry::query($this->tag)->fetch();
        $ArtistEntry = $OldEntry ?: new ArtistEntry($this->tag);

        $Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
            new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),

            new HTMLElement('fieldset', 'fieldset-artist-info inline',
                new HTMLElement('legend', 'legend-artist-info', 'Artist Info'),

                new MapRenderer($ArtistEntry)
            ),


            new HTMLElement('fieldset', 'fieldset-artist-manage inline',
                new HTMLElement('legend', 'legend-artist-manage', 'Manage'),

                new HTMLPathTip($Request, '#tip-select', self::TIP_MANAGE_ARTIST),

                new HTMLElement('label', null, "Artist Name:<br/>",
                    new HTMLInputField(self::PARAM_ARTIST_NAME, $ArtistEntry->getName(),
                        new Attributes('placeholder', 'i.e. "Sting"'),
                        new RequiredValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Artist URL:<br/>",
                    new HTMLInputField(self::PARAM_ARTIST_URL, $ArtistEntry->getURL(),
                        new Attributes('placeholder', 'i.e. "Sting"'),
                        new URLValidation()
                    )
                ),

                "<br/><br/>",
                new HTMLElement('label', null, "Artist Description:<br/>",
                    new HTMLTextAreaField(self::PARAM_ARTIST_DESCRIPTION, $ArtistEntry->getDescription(),
                        new Attributes('rows', 10, 'cols', 40),
                        new Attributes('placeholder', 'i.e. "Artist Description/Bio"')
                    )
                ),

                "<br/>Allowed Tags:<br/>",
                "<div class='info'>&#60;" . implode('&#62;, &#60;', Config::$AllowedTags) . '&#62;</div>',

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
            )
        );

        if (!$Request instanceof IFormRequest)
            return $Form;

        $submit = $Form->validateField($Request, self::PARAM_SUBMIT);

        switch ($submit) {
            case 'update':
                $name = $Form->validateField($Request, self::PARAM_ARTIST_NAME);
                $url = $Form->validateField($Request, self::PARAM_ARTIST_URL);
                $description = $Form->validateField($Request, self::PARAM_ARTIST_DESCRIPTION);
//                $status = $Form->validateField($Request, self::PARAM_ARTIST_STATUS);
                $ArtistEntry->update($Request, $name, $description, $url);
                return new RedirectResponse(ManageArtist::getRequestURL($ArtistEntry->getName()), "Updated Artist successfully. Such the artisan...", 5);

            default:
                throw new \InvalidArgumentException("Invalid submit: " . $submit);
        }
    }

	// Static

	public static function getRequestURL($artist) {
		return str_replace(':' . self::PARAM_ARTIST_NAME, urlencode($artist), self::FORM_ACTION);
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
		return new ExecutableRenderer(new static(urldecode($Request[self::PARAM_ARTIST_NAME])), true);
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