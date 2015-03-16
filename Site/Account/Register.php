<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 9/17/14
 * Time: 8:14 AM
 */
namespace Site\Account;

use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Render\HTML\Attribute\Attributes;
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLFileInputField;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\Form\HTMLInputField;
use CPath\Render\HTML\Element\Form\HTMLPasswordField;
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
use CPath\Request\Validation\Exceptions\ValidationExcepton;
use CPath\Request\Validation\RequiredValidation;
use CPath\Request\Validation\UserNameOrEmailValidation;
use CPath\Request\Validation\ValidationCallback;
use CPath\Response\Common\RedirectResponse;
use CPath\Route\IRoutable;
use CPath\Route\RouteBuilder;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\DB\AccountTable;
use Site\Account\Guest\TestAccount;
use Site\Config;
use Site\Path\HTML\HTMLPathTip;
use Site\PGP\Commands\PGPDeletePublicKeyCommand;
use Site\PGP\Commands\PGPImportPublicKeyCommand;
use Site\PGP\Commands\PGPSearchCommand;
use Site\PGP\Exceptions\PGPKeyAlreadyImported;
use Site\PGP\PublicKey;
use Site\Relay\HTML\HTMLRelayChat;
use Site\SiteMap;

class Register implements IExecutable, IBuildable, IRoutable, ITestable
{
	const CLS_FIELDSET_TOOLS = 'fieldset-tools';
	const CLS_FORM = 'form-register';

    const FORM_ACTION = '/register/';
	const FORM_NAME = 'form-register';

	const PARAM_PUBLIC_KEY = 'public_key';
	const PARAM_RESET = 'reset';
	const PARAM_GENERATE = 'gen-keys';
	const PARAM_USER = 'gen-user';
	const PARAM_EMAIL = 'gen-email';
	const PARAM_PASSPHRASE = 'passphrase';
	const PARAM_SUBMIT = 'submit';
	const PARAM_LOAD_FILE = 'load-file';
	const PARAM_LOAD_STORAGE = 'load-storage';

//	const REGISTRATION_LIMIT = 86400;

	const PLACEHOLDER = "-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v1

...
...
...
-----END PGP PUBLIC KEY BLOCK-----";

    const TIPS_GEN = "<b>Generate PGP Key Pair</b><br/><br/>This fieldset generates a new PGP Key pair and stores it on your browser. Only the public key is sent to the server. The PGP passphrase field is <b>optional</b>.";
    const TIPS_PGP = "<b>PGP Public Key</b><br/><br/>This fieldset contains the PGP Public Key used to create your new account";
    const TIPS_PGP_LOAD_STORAGE = "<b>Load from Browser</b><br/><br/>This button will cycle through any PGP Key pairs stored on your browser and load the public key";
    const TIPS_PGP_LOAD_FILE = "<b>Load from File</b><br/><br/>This field allows you to load a PGP Public Key from a text file";

    private $mNewAccountFingerprint = null;

	public function getRequestPath() {
		return self::FORM_ACTION;
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request the request to execute
	 * @throws \CPath\Request\Exceptions\RequestException
	 * @throws \Exception
	 * @return HTMLForm
	 */
    function execute(IRequest $Request) {
        $inviteeEmail = null;
        $inviteFingerprint = null;
        $isInvite = false;
        if($Request instanceof ISessionRequest) {
            if(Invite::hasInviteContent($Request)) {
                $isInvite = true;
                list($inviteeEmail, $inviteFingerprint) = Invite::getInviteContent($Request);
            }
        }

        if(Config::$RequireInvite && !$isInvite)
            throw new \Exception("Registration requires an invitation from an existing user in the community");

	    $Form = new HTMLForm('POST', self::FORM_ACTION, self::FORM_NAME, self::CLS_FORM,
		    new HTMLMetaTag(HTMLMetaTag::META_TITLE, 'User Registration'),

		    new HTMLHeaderScript(__DIR__ . '\assets\form-register.js'),
		    new HTMLHeaderStyleSheet(__DIR__ . '\assets\form-register.css'),

		    new HTMLElement('h2', 'content-title', 'Registration'),

		    new HTMLElement('fieldset', 'fieldset-generate',
			    new HTMLElement('legend', 'legend-generate toggle', "Generate a new PGP key pair to secure your personal information"),

                new HTMLPathTip($Request, '#gen-tips', self::TIPS_GEN),

			    "Choose a new user ID<br/>",
			    new HTMLInputField(self::PARAM_USER),

			    "<br/><br/>Please provide a public email address<br/>",
			    new HTMLInputField(self::PARAM_EMAIL,
                    ($inviteeEmail ? new Attributes('disabled', 'disabled') : null)
                ),

			    "<br/><br/>Choose an <b>optional</b> passphrase for your private key<br/>",
			    new HTMLPasswordField(self::PARAM_PASSPHRASE, 'field-passphrase'),

			    "<br/><br/>Generate your user account PGP key pair<br/>",
			    new HTMLButton(self::PARAM_GENERATE, 'Generate', null, null, 'field-generate',
				    new Attributes('disabled', 'disabled')
			    )
		    ),

		    "<br/><br/>",
		    new HTMLElement('fieldset', 'fieldset-public-key',
			    new HTMLElement('legend', 'legend-public-key toggle', "Your PGP Public Key"),

                new HTMLPathTip($Request, '#pgp-tips', self::TIPS_PGP),

			    "Enter a PGP public key you'll use to identify yourself publicly<br/>",
			    new HTMLTextAreaField(self::PARAM_PUBLIC_KEY, null, 'field-public-key',
				    new Attributes('rows', 14, 'cols', 80),
				    new Attributes('placeholder', self::PLACEHOLDER),
				    new RequiredValidation(),
				    new ValidationCallback(
					    function (IRequest $Request, $publicKeyString) {
						    $PublicKey = new PublicKey($publicKeyString);
						    $userID    = $PublicKey->getUserID();

						    $Validation = new UserNameOrEmailValidation();
						    $Validation->validate($Request, $userID);

						    $shortKey  = $PublicKey->getKeyID();
						    $PGPSearch = new PGPSearchCommand($shortKey, '');
						    $PGPSearch->executeWithCallback($Request, function () use ($shortKey) {
							    throw new \Exception("Short key conflict: " . $shortKey);
						    });

						    $timestamp = $PublicKey->getTimestamp();
						    if (Config::$REGISTRATION_LIMIT !== false
							    && ($timestamp < time() - Config::$REGISTRATION_LIMIT - 24*60*60)	// - 1 day buffer
						    )
							    throw new \Exception("Provided public key was created more than 24 hours ago. Please register with a new key pair");
					    }
				    )
			    ),

			    "<br/>",
			    new HTMLElement('fieldset', 'fieldset-load-file inline',
				    new HTMLElement('legend', 'legend-tools toggle', "Load PGP Public Key File"),
                    new HTMLPathTip($Request, '#gen-tips', self::TIPS_PGP_LOAD_FILE),

                    "Upload:<br/>",
				    new HTMLFileInputField(self::PARAM_LOAD_FILE, '.pub, .asc', 'field-load')
			    ),
			    new HTMLElement('fieldset', 'fieldset-load-storage inline',
				    new HTMLElement('legend', 'legend-storage toggle', "Load From Storage"),
                    new HTMLPathTip($Request, '#gen-tips', self::TIPS_PGP_LOAD_STORAGE),
                    new HTMLButton(self::PARAM_LOAD_STORAGE, "Load",
					    new Attributes('disabled', 'disabled')
				    )
			    )
		    ),

            "<br/><br/>",
            new HTMLElement('fieldset', 'fieldset-submit',
                new HTMLElement('legend', 'legend-submit', "Submit Registration"),
                new HTMLButton(self::PARAM_SUBMIT, 'Register', null, 'submit', 'field-submit'),
                new HTMLButton(self::PARAM_RESET, 'Reset Form', null, 'reset', 'field-reset')
            )

	    );

        $Form->addContent(new HTMLRelayChat($Request, 'public-chat-registration'), IHTMLContainer::KEY_RENDER_CONTENT_AFTER);

	    $Form->setFormValues($Request);
	    if(!$Request instanceof IFormRequest)
		    return $Form;


	    $publicKeyString = $Form->validateField($Request, self::PARAM_PUBLIC_KEY, 0);

        $Account = AccountEntry::create($Request, $publicKeyString, $inviteeEmail, $inviteFingerprint);
        $fingerprint = $Account->getFingerprint();
        $this->mNewAccountFingerprint = $fingerprint;

//	    } catch (PGPKeyAlreadyImported $ex) {
//		    throw new ValidationException($Form, $ex->getMessage());
//	    }

	    $Account = AccountEntry::get($fingerprint);

//	    if($Request instanceof ISessionRequest) {
//		    if($Request->isStarted())
//			    $Request->endSession();
//		    $Request->startSession();
//		    //UserSession::setUserFingerprintFromSession($Request, $User->getFingerprint());
//	    }
        if($Request instanceof ISessionRequest)
            $Request->destroySession();
	    return new RedirectResponse(Login::getRequestURL($Account->getFingerprint()),
		    "User registered successfully - " . $Account->getName(), 5);
    }

	public function getNewAccountFingerprint() {
		if(!$this->mNewAccountFingerprint)
			throw new \InvalidArgumentException("Execution did not complete");
		return $this->mNewAccountFingerprint;
	}

    // Static

    public static function getRequestURL() {
        return self::FORM_ACTION;
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
            IRequest::NAVIGATION_NO_LOGIN |
            IRequest::NAVIGATION_ROUTE,
            "Register");
    }

	/**
	 * Route the request to this class object and return the object
	 * @param IRequest $Request the IRequest inst for this render
	 * @param Object[]|null $Previous all previous response object that were passed from a handler, if any
	 * @param null|mixed $_arg [varargs] passed by route map
	 * @return void|bool|Object returns a response object
	 * If nothing is returned (or bool[true]), it is assumed that rendering has occurred and the request ends
	 * If false is returned, this static handler will be called again if another handler returns an object
	 * If an object is returned, it is passed along to the next handler
	 */
	static function routeRequestStatic(IRequest $Request, Array &$Previous = array(), $_arg = null) {
		return new ExecutableRenderer(new Register(), true);
	}
//
//	/**
//	 * Perform a unit test
//	 * @param IUnitTestRequest $Test the unit test request inst for this test session
//	 * @return void
//	 * @test --disable 0
//	 * Note: Use doctag 'test' with '--disable 1' to have this ITestable class skipped during a build
//	 */
//	static function handleStaticUnitTest(IUnitTestRequest $Test) {
//		$TestUser = new TestUser($Test, 'register');
////		$TestUser->deleteAccount($Test);
////		$TestUser = new TestUser($Test, 'register');
//
//		$publicKey = $TestUser->exportPublicKey($Test);
//		$privateKey = $TestUser->exportPrivateKey($Test);
//		$TestUser->deleteAccount($Test);
//
//		$TestUser->importPrivateKey($Test, $privateKey);
//		$TestUser->deleteAccount($Test);
//
//		Register::$mTestMode = true;
//		$Register = new Register();
//		Register::$mTestMode = false;
//
//		$Test->setRequestParameter(Register::PARAM_PUBLIC_KEY, $publicKey);
//		$Register->execute($Test);
//		$fp = $Register->getNewAccountFingerprint();
//		$Test->assertEqual($fp, $TestUser->getFingerprint());
//
//		$TestUser->importPrivateKey($Test, $privateKey);
//
//	}

    const TEST_FINGERPRINT = '3ad63323f7969265';
    const TEST_USER = 'test-user';
    const TEST_USER_EMAIL = 'test-user@email.com';
    const TEST_PUBLIC_KEY = "-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: OpenPGP.js v0.8.2
Comment: http://openpgpjs.org

xo0EVPE+twEEAJj43I2tMlPN0F2LKAySznAv0HBeCAwLD+I/vCwDzdehQuYa
VBIues1ATl4IR1eOeHLX/9F2DdlfiR5aZKut605B++WflRuZluEN55tsulkm
ztcW8HZ/ANRMgcFjd03yc8LYYGcfzApkShlxmUU7jlVlca7m0Ysc8TrIPFkK
gd4DABEBAAHNH3Rlc3QtdXNlciA8dGVzdC11c2VyQGVtYWlsLmNvbT7CsgQQ
AQgAJgUCVPE+uAYLCQgHAwIJEGhiAsB44CiXBBUIAgoDFgIBAhsDAh4BAAAv
pQQAgMpH0SMGzWZlIOoydK8/qm2bayrurFd1GaRpCgv2o2zQLnQMsz/tTQ75
4a7oS4kRY73dgsKlqnyb7P0vCeQ+fHgULgHK+1pGmZwXsNkL10xf4xiDfG7/
p9ippCuBhP5//1CzgWXXwbhW15xcZyXHOZbD9JODBERIJ6NxnbGCnBLOjQRU
8T64AQQAhnDuHcmu/pNbp1xvYQkVBEFxqjJQHNchDJO8Cjjm11OPurTiv3s7
XeYRFH/4ulXihJnRpWjZxaoM+CmOIuWbYdaw1emgpWPjyeGqs8XtWzPg4tEl
Bg7WaP7RtlALpjg++PN71T/Gu9oqfxqx2tFp+6grUV0zvd8yV8dmIBob1UUA
EQEAAcKfBBgBCAATBQJU8T64CRBoYgLAeOAolwIbDAAAxe4D/3kSuu3eUu79
9+0BBlZu35fzwy9Q+T/KzUQTGOHv8Kle8e/rlFyN1PpG8nHVv8DWQNb58ETk
x5HywLEjP++B8H5ldWilYa5NfOvPaZai76qRBrzqaLsrwd5sL5QHxUkxIuOa
wC4LtwPVHIpRsVpM3/4Z7eakculsOi5+J/wz93xr
=cbho
-----END PGP PUBLIC KEY BLOCK-----

;";

    /**
     * Perform a unit test
     * @param IUnitTestRequest $Test the unit test request inst for this test session
     * @return void
     * @test --disable 0
     * Note: Use doctag 'test' with '--disable 1' to have this ITestable class skipped during a build
     */
    static function handleStaticUnitTest(IUnitTestRequest $Test) {

        $Register = new Register();

        $OldTestAccount = AccountEntry::table()
            ->fetch(AccountTable::COLUMN_EMAIL, 'test-user@email.com');

        if($OldTestAccount)
            AccountEntry::delete($Test, $OldTestAccount->getFingerprint());

        $Test->setRequestParameter(self::PARAM_EMAIL, self::TEST_USER_EMAIL);
        $Test->setRequestParameter(self::PARAM_USER, self::TEST_USER);
        $Test->setRequestParameter(self::PARAM_PUBLIC_KEY, self::TEST_PUBLIC_KEY);

        try {
            $Response = $Register->execute($Test);
        } catch (PGPKeyAlreadyImported $ex) {

        }

        $PGPDelete = new PGPDeletePublicKeyCommand(self::TEST_FINGERPRINT);
        $PGPDelete->setPrimaryKeyRing(AccountEntry::KEYRING_NAME);
        $PGPDelete->execute($Test);
    }
}

