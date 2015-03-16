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
use CPath\Render\HTML\IHTMLContainer;
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
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\Guest\TestAccount;
use Site\Path\HTML\HTMLPathTip;
use Site\PGP\Exceptions\PGPKeyAlreadyImported;
use Site\Relay\HTML\HTMLRelayChat;
use Site\SiteMap;

class Login implements IExecutable, IBuildable, IRoutable, ITestable
{
    const FIELDSET_PASSPHRASE = 'fieldset-passphrase';
    const FIELDSET_CHALLENGE = 'fieldset-challenge';

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

    const TIP_SELECT = '<b>PGP User IDs</b><br /><br />Select your user ID from the menu.
If your user ID does not appear, it may not be stored on your browser';
    const TIP_CHALLENGE = '<b>Login Challenge</b><br /><br />This is your login <b>grant challenge</b>.
In order to log in, the challenge must be decrypted using your <b>private key</b> in order to prove your identity.
If your private key is stored on your browser, the challenge should be automatically solved.
Once the <b>challenge answer</b> is entered, you may log in';

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

			new HTMLElement('h2', 'content-title', 'Log in'), // as ' . $this->getUser()->getPGPKey()->getUserID()),

			new HTMLElement('fieldset',
				new HTMLElement('legend', 'legend-submit', "Enter user id"),

                new HTMLPathTip($Request, '#tip-select', self::TIP_SELECT),

				new HTMLInputField(self::PARAM_FINGERPRINT,
					new RequiredValidation()
				),
                "<br/><br/>",
				new HTMLSubmit('Submit')
			)
		);

        $FormSelectFingerprint->addContent(new HTMLRelayChat($Request, 'public-chat-login'), IHTMLContainer::KEY_RENDER_CONTENT_AFTER);

        if(!isset($Request[self::PARAM_FINGERPRINT]))
			return $FormSelectFingerprint;

		$fingerprint = $Request[self::PARAM_FINGERPRINT];

		try {
			$Account = AccountEntry::get($fingerprint);
		} catch (\Exception $ex) {
			throw new ValidationException($FormSelectFingerprint, $ex->getMessage(), $ex);
		}
		$requestPath = self::getRequestURL($fingerprint);

        // $Account->generateChallenge($Request);
		$challenge = $Account->loadChallenge($Request);

		$Form = new HTMLForm(self::FORM_METHOD, $requestPath, static::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, 'User Login'),

			new HTMLHeaderScript(__DIR__ . '\assets\form-login.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '\assets\form-login.css'),

			new Attributes('autocomplete', 'off'),

			new HTMLInputField(self::PARAM_FINGERPRINT, $fingerprint, 'hidden'),

			new HTMLElement('fieldset', self::FIELDSET_CHALLENGE . ' toggle',
				new HTMLElement('legend', 'legend-challenge', "Grant Challenge"),

                new HTMLPathTip($Request, '#tip-challenge', self::TIP_CHALLENGE),

                new HTMLElement('fieldset', self::FIELDSET_PASSPHRASE,
					new HTMLElement('legend', 'legend-passphrase', "Please enter PGP Passphrase"),

					new HTMLPasswordField(self::PARAM_PASSPHRASE,
						new Attributes('disabled', 'disabled')
					)

				),

                "<br/>Encrypted Challenge<br/>",
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

        $Form->addContent(new HTMLRelayChat($Request, 'public-chat-login'), IHTMLContainer::KEY_RENDER_CONTENT_AFTER);

        if(!$Request instanceof IFormRequest)
			return $Form;

		$Form->validateRequest($Request);
		$answer = $Form->validateField($Request, self::PARAM_CHALLENGE_ANSWER);
		$Account->assertChallengeAnswer($Request, $answer, $Form);
		$Account->generateChallenge($Request, array($Account->getFingerprint()));

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
			| IRequest::NAVIGATION_NO_LOGIN
			, "Login");
	}

    /**
     * Perform a unit test
     * @param IUnitTestRequest $Test the unit test request inst for this test session
     * @return void
     * @test --disable 0
     * Note: Use doctag 'test' with '--disable 1' to have this ITestable class skipped during a build
     */
    static function handleStaticUnitTest(IUnitTestRequest $Test) {
        try { AccountEntry::create($Test, TestAccount::PGP_PUBLIC_KEY); }
        catch (\Exception $ex) {}
//        $Login = new Login();
//
//        $Test->setRequestParameter(self::PARAM_FINGERPRINT, Register::TEST_FINGERPRINT);
//        $Test->setRequestParameter(self::PARAM_CHALLENGE_ANSWER, Register::TEST_USER_EMAIL);
//        $Login->execute($Test);

    }
}