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
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLPasswordField;
use CPath\Render\HTML\Element\Form\HTMLSubmit;
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
use CPath\Request\Validation\Exceptions\ValidationException;
use CPath\Request\Validation\RequiredValidation;
use CPath\Response\Common\RedirectResponse;
use CPath\Response\IResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use Site\Account\DB\AccountEntry;
use Site\SiteMap;

class Login implements IExecutable, IBuildable, IRoutable
{
	const CLS_FIELDSET_ACCOUNT = 'fieldset-account';
	const CLS_FIELDSET_CONFIG = 'fieldset-account-config';

	const TITLE = 'Login';

	const FORM_ACTION = '/login/';
	const FORM_PATH = '/login/:fingerprint';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'login';

	const PARAM_ACCOUNT_NAME = 'account-name';
	const PARAM_ACCOUNT_PASSWORD = 'account-password';
	const PARAM_FINGERPRINT = 'fingerprint';
	const PARAM_CHALLENGE = 'challenge';
	const PARAM_CHALLENGE_ANSWER = 'challenge-answer';
	const PARAM_PASSPHRASE = 'passphrase';

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

		$FormSelectFingerprint = new HTMLForm(self::FORM_METHOD, $requestPath, self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, 'User Log in'),

			new HTMLHeaderScript(__DIR__ . '\assets\form-login.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '\assets\form-login.css'),

			new HTMLElement('legend', 'content-title', 'Log in'), // as ' . $this->getUser()->getPGPKey()->getUserID()),

			new HTMLElement('fieldset',
				new HTMLElement('legend', 'legend-submit', "Enter user id"),

				new HTMLInputField(self::PARAM_FINGERPRINT,
					new RequiredValidation()
				),
				new HTMLSubmit('Submit')
			)
		);

		if(!isset($Request[self::PARAM_FINGERPRINT]))
			return $FormSelectFingerprint;

		$fingerprint = $Request[self::PARAM_FINGERPRINT];

		try {
			$Account = AccountEntry::get($fingerprint);
		} catch (\Exception $ex) {
			throw new ValidationException($FormSelectFingerprint, $ex->getMessage(), $ex);
		}
		$requestPath = self::getRequestURL($fingerprint);

		$challenge = $Account->loadChallenge($Request);

		$Form = new HTMLForm(self::FORM_METHOD, $requestPath, static::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, 'User Login'),

			new HTMLHeaderScript(__DIR__ . '\assets\form-login.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '\assets\form-login.css'),

			new Attributes('autocomplete', 'off'),

			new HTMLInputField(self::PARAM_FINGERPRINT, $fingerprint, 'hidden'),

			new HTMLElement('fieldset', 'fieldset-challenge toggle',
				new HTMLElement('legend', 'legend-challenge', "Grant Challenge"),

				new HTMLElement('fieldset', 'fieldset-passphrase inline',
					new HTMLElement('legend', 'legend-passphrase', "Please enter PGP Passphrase"),

					new HTMLPasswordField(self::PARAM_PASSPHRASE,
						new Attributes('disabled', 'disabled')
					),

					"<br/><br/>"
				),

				"<br/><br/>",

				$FieldChallenge = new HTMLTextAreaField(self::PARAM_CHALLENGE, $challenge,
					new Attributes('rows', 14, 'cols', 80)
				),

				"<br/><br/>Grant Answer<br/>",
				$FieldAnswer = new HTMLInputField(self::PARAM_CHALLENGE_ANSWER,
					new RequiredValidation()
				),

				"<br/><br/>",
				new HTMLSubmit('submit', 'Login')
			)
		);

		$Form->setFormValues($Request);

		if(!$Request instanceof IFormRequest)
			return $Form;

		$Form->validateRequest($Request);
		$answer = $Form->validateField($Request, self::PARAM_CHALLENGE_ANSWER);
		$Account->assertChallengeAnswer($answer, $Form);
		$Account->generateChallenge($Request);

		$Account->startSession($SessionRequest);

		return new RedirectResponse(AccountHome::getRequestURL(), "Logged in successfully. Redirecting...", 3);
	}

	// Static

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
		$RouteBuilder->writeRoute('ANY ' . self::FORM_PATH, __CLASS__,
			IRequest::NAVIGATION_ROUTE
			| IRequest::MATCH_NO_SESSION
			, "Login");
	}
}