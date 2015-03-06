<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 1/27/2015
 * Time: 1:56 PM
 */
namespace Site\Account;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLSubmit;
use CPath\Render\HTML\Element\Form\HTMLTextAreaField;
use CPath\Render\HTML\Element\HTMLElement;
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
use Site\Account\DB\AccountEntry;
use Site\PGP\Commands\PGPDecryptCommand;
use Site\SiteMap;

class Invite implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Accept Invitation';

	const FORM_ACTION = '/invite/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'invite';

	const PARAM_INVITE = 'invite';
    const SESSION_KEY_INVITE_EMAIL = 'invite-content';
    const SESSION_KEY_INVITE_FINGERPRINT = 'invite-fingerprint';

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

		$requestPath = self::getRequestURL();

		$Form = new HTMLForm(self::FORM_METHOD, $requestPath, self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, 'Accept Invitation'),

//			new HTMLHeaderScript(__DIR__ . '\assets\invite.js'),
//			new HTMLHeaderStyleSheet(__DIR__ . '\assets\invite.css'),

			new HTMLElement('h2', 'content-title', 'Accept Invitation'), // as ' . $this->getUser()->getPGPKey()->getUserID()),

			new HTMLElement('fieldset',
				new HTMLElement('legend', 'legend-submit', "Enter invite content"),

				new HTMLTextAreaField(self::PARAM_INVITE,
                    new Attributes('cols', 50, 'rows', 20),
					new RequiredValidation()
				),

                "<br/><br/>",
				new HTMLSubmit('Accept Invite')
			)
		);

        $Form->setFormValues($Request);

		if(!$Request instanceof IFormRequest)
			return $Form;

		$Form->validateRequest($Request);
		$invite = $Form->validateField($Request, self::PARAM_INVITE);

        $PGPDecrypt = new PGPDecryptCommand($invite);
        $PGPDecrypt->execute($Request);
        $fps = $PGPDecrypt->getSignIDs();

        if(sizeof($fps) !== 1)
            throw new \InvalidArgumentException("Invalid number of fingerprints");

        $keyID = strtoupper($fps[0]);

        $Account = AccountEntry::fetch('%' . $keyID, " LIKE ?");
        $inviteEmail = trim($Account->verify($Request, $invite));

        $SessionRequest->startSession();
        $Session = &$SessionRequest->getSession();
        $Session[self::SESSION_KEY_INVITE_EMAIL] = $inviteEmail;
        $Session[self::SESSION_KEY_INVITE_FINGERPRINT] = $Account->getFingerprint();
        $SessionRequest->endSession();

		return new RedirectResponse(Register::getRequestURL(), "Invite decrypted successfully. Redirecting to registration...", 5);
	}

	// Static

    public static function hasInviteContent(ISessionRequest $Request) {
        $started = $Request->isStarted();
        if(!$started)
            $Request->startSession();

        $Session = &$Request->getSession();
        $hasContent = isset($Session[self::SESSION_KEY_INVITE_EMAIL])  && isset($Session[self::SESSION_KEY_INVITE_FINGERPRINT]);

        if(!$started)
            $Request->endSession();
        return $hasContent;
    }

    public static function getInviteContent(ISessionRequest $Request) {
        $started = $Request->isStarted();
        if(!$started)
            $Request->startSession();

        $Session = &$Request->getSession();
        $inviteEmail = $Session[self::SESSION_KEY_INVITE_EMAIL];
        if(!$inviteEmail)
            throw new \InvalidArgumentException("Invitee Email not found in session");
        $fingerprint = $Session[self::SESSION_KEY_INVITE_FINGERPRINT];
        if(!$fingerprint)
            throw new \InvalidArgumentException("Invite Fingerprint not found in session");

        if(!$started)
            $Request->endSession();
        return array($inviteEmail, $fingerprint);
    }

	public static function getRequestURL($fingerprint=null) {
		return self::FORM_ACTION . ($fingerprint ? $fingerprint . '/': '');
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
		return new ExecutableRenderer(new static(), true);
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