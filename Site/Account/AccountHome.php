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
use CPath\Render\HTML\HTMLConfig;
use CPath\Render\Map\MapRenderer;
use CPath\Request\Exceptions\RequestException;
use CPath\Request\Executable\ExecutableRenderer;
use CPath\Request\Executable\IExecutable;
use CPath\Request\Form\IFormRequest;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Account\DB\AccountEntry;
use Site\Account\Session\AccountSession;
use Site\Account\Session\DB\SessionEntry;
use Site\Account\Session\HTML\HTMLSessionsTable;
use Site\SiteMap;

class AccountHome implements IExecutable, IBuildable, IRoutable
{
	const TITLE = 'Account Home';

	const FORM_ACTION = '/home/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = __CLASS__;

	const PARAM_SUBMIT = 'submit';
    const PARAM_ACCOUNT_FINGERPRINT = 'account-fingerprint';
    const PARAM_INVITE_EMAIL = 'invite-email';
    const PARAM_INVITE_MESSAGE = 'invite-message';
    const PARAM_INVITE_CONTENT = 'invite-content';
    const PARAM_GENERATE_INVITE = 'generate-invite';
    const CLS_ANCHOR_SEND_EMAIL = 'send-email';
    const PARAM_PRIVATE_MESSAGE = 'private-message';
    const PARAM_PRIVATE_MESSAGE_RECIPIENT = 'private-message-recipient';
    const PARAM_ENCRYPT = 'encrypt';
    const PARAM_PGP_SELECT = 'pgp-select';
    const PARAM_PGP_BACKUP = 'pgp-backup';
    const PARAM_PGP_DELETE = 'pgp-delete';
    const PARAM_PGP_CONTENT = 'pgp-content';
    const PARAM_PGP_ADD = 'pgp-add';

    /**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws RequestException
	 * @throws \CPath\Request\Validation\Exceptions\ValidationException
	 * @throws \Exception
	 * @return IResponse the execution response
	 */
	function execute(IRequest $Request) {
        $SessionRequest = $Request;
        if (!$SessionRequest instanceof ISessionRequest)
            throw new \Exception("Session required");

        $Account = AccountEntry::loadFromSession($SessionRequest);

        $SessionsTable = new HTMLSessionsTable($Account->getFingerprint());

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
			new HTMLHeaderScript(__DIR__ . '/assets/account.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/account.css'),

            new HTMLInputField(self::PARAM_ACCOUNT_FINGERPRINT, $Account->getFingerprint(), 'hidden'),

			new HTMLElement('fieldset', 'fieldset-info inline',
				new HTMLElement('legend', 'legend-info', self::TITLE),

				new MapRenderer($Account)
			),

			"<br/>",

            new HTMLElement('fieldset', 'fieldset-manage inline',
                new HTMLElement('legend', 'legend-manage', "Manage Account"),

                "<br/><br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Update', 'update')
            ),

            new HTMLElement('fieldset', 'fieldset-invite inline',
                new HTMLElement('legend', 'legend-invite', "Create an Invite"),

                "Invitee's Email Address:<br/>",
                new HTMLInputField(self::PARAM_INVITE_EMAIL),

                "<br/><br/>Add an invite message:<br/>",
                new HTMLTextAreaField(self::PARAM_INVITE_MESSAGE
//                    new Attributes('cols', 40)
                ),

                "<br/><br/>Generated invite:<br/>",
                new HTMLTextAreaField(self::PARAM_INVITE_CONTENT
//                    new Attributes('cols', 40)
                ),

                "<br/><br/>",
                new HTMLButton(self::PARAM_GENERATE_INVITE, 'Generate', self::PARAM_GENERATE_INVITE,
                    new Attributes('disabled', 'disabled')
                ),

                new HTMLAnchor('#', "Send Email", self::CLS_ANCHOR_SEND_EMAIL  . ' ' . HTMLConfig::$DefaultInputClass
                )
            ),

            new HTMLElement('fieldset', 'fieldset-private-message inline',
                new HTMLElement('legend', 'legend-private-message', "Create a Private Message"),

                "Recipient's Email Address or User ID:<br/>",
                new HTMLInputField(self::PARAM_PRIVATE_MESSAGE_RECIPIENT),

                "<br/><br/>Private Message:<br/>",
                new HTMLTextAreaField(self::PARAM_PRIVATE_MESSAGE,
                    new Attributes('cols', 40, 'rows', 12)
                ),

                "<br/><br/>Encrypt:<br/>",
                new HTMLButton(self::PARAM_ENCRYPT, 'Encrypt For Recipient(s)',
                    new Attributes('disabled', 'disabled')
                ),

                "<br/><br/>Send:<br/>",
                new HTMLButton(self::PARAM_SUBMIT, 'Send Message', self::PARAM_PRIVATE_MESSAGE,
                    new Attributes('disabled', 'disabled')
                )
            ),

            new HTMLElement('fieldset', 'fieldset-sessions inline',
                new HTMLElement('legend', 'legend-sessions', "Session History"),

                $SessionsTable

            ),

            new HTMLElement('fieldset', 'fieldset-manage-pgp inline',
                new HTMLElement('legend', 'legend-manage-pgp', "Manage Browser Keys"),

                new HTMLSelectField(self::PARAM_PGP_SELECT, 'select-pgp-list'),

                "<br/><br/>",
                new HTMLTextAreaField(self::PARAM_PGP_CONTENT),

                "<br/><br/>",
                new HTMLButton(self::PARAM_PGP_ADD, 'Add',
                    new Attributes('disabled', 'disabled')
                ),
                new HTMLButton(self::PARAM_PGP_BACKUP, 'Backup',
                    new Attributes('disabled', 'disabled')
                ),
                new HTMLButton(self::PARAM_PGP_DELETE, 'Delete',
                    new Attributes('disabled', 'disabled')
                )
            )

		);

		if(!$Request instanceof IFormRequest)
			return $Form;

		$submit = $Request[self::PARAM_SUBMIT];

		switch($submit) {
			case 'update':
//				$status = $Form->validateField($Request, self::PARAM_ACCOUNT_STATUS);
//				$Account->update($Request, $Account, $status);
				return new RedirectResponse(AccountHome::getRequestURL(), "Account updated successfully. Redirecting...", 5);
		}

		throw new \InvalidArgumentException($submit);
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

		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION, __CLASS__,
			IRequest::NAVIGATION_LOGIN_ONLY
			| IRequest::NAVIGATION_ROUTE,
			"My Account");
	}
}
