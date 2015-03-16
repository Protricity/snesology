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
use CPath\Render\HTML\Element\Form\HTMLButton;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\Element\HTMLElement;
use CPath\Render\HTML\Header\HTMLHeaderScript;
use CPath\Render\HTML\Header\HTMLHeaderStyleSheet;
use CPath\Render\HTML\Header\HTMLMetaTag;
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
use Site\SiteMap;

class Logout implements IExecutable, IBuildable, IRoutable
{
	const CLS_FIELDSET_ACCOUNT = 'fieldset-account';
	const CLS_FIELDSET_CONFIG = 'fieldset-account-config';

	const TITLE = 'Logout';

	const FORM_ACTION = '/logout/';
	const FORM_METHOD = 'POST';
	const FORM_NAME = 'logout';

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

		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE . ' - ' . $Account->getName()),
			new HTMLHeaderScript(__DIR__ . '/assets/account.js'),
			new HTMLHeaderStyleSheet(__DIR__ . '/assets/account.css'),

			new HTMLElement('fieldset', 'fieldset-create-account',
				new HTMLElement('legend', 'legend-account', self::TITLE . ' - ' . $Account->getName()),

				new HTMLButton('submit', 'Logout', 'submit')
			),
			"<br/>"
		);

		$Form->setFormValues($Request);

		if(!$Request instanceof IFormRequest)
			return $Form;

		$Form->validateRequest($Request);

		if(!$SessionRequest->isStarted())
			$SessionRequest->startSession();
		$SessionRequest->destroySession();

		return new RedirectResponse(Login::getRequestURL(), "Logged out successfully. Redirecting...", 5);
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
		$RouteBuilder = new RouteBuilder($Request, new SiteMap(), '_1logout');
		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION, __CLASS__,
			IRequest::NAVIGATION_ROUTE
			| IRequest::NAVIGATION_LOGIN_ONLY
			, "Logout");
	}
}

//class CreateAccount implements IExecutable, IBuildable, IRoutable
//{
//	const TITLE = 'Create Account';
//
//	const FORM_ACTION = '/accounts';
//	const FORM_ACTION2 = '/a/:id';
//	const FORM_ACTION3 = '/manage/account/:id';
//	const FORM_METHOD = 'POST';
//	const FORM_NAME = __CLASS__;
//
//	const PARAM_ID = 'id';
//
//	/**
//	 * Execute a command and return a response. Does not render
//	 * @param IRequest $Request
//	 * @return IResponse the execution response
//	 */
//	function execute(IRequest $Request) {
//		$Form = new HTMLForm(self::FORM_METHOD, $Request->getPath(), self::FORM_NAME,
//			new HTMLMetaTag(HTMLMetaTag::META_TITLE, self::TITLE),
////			new HTMLHeaderScript(__DIR__ . '\assets\form-login.js'),
////			new HTMLHeaderStyleSheet(__DIR__ . '\assets\form-login.css'),
//
////			new HTMLElement('h3', null, self::TITLE),
//
//			new HTMLElement('fieldset',
//				new HTMLElement('legend', 'legend-wallet', self::TITLE),
//
//				"Wallet ID:<br/>",
//				new HTMLInputField(TransactionTable::COLUMN_WALLET_ID,
//					new RequiredValidation()
//				),
//				new HTMLButton('submit', 'Submit', 'submit')
//			),
//
//			new HTMLElement('fieldset',
//				new HTMLElement('legend', 'legend-transaction', self::TITLE),
//
//				"Account ID:<br/>",
//				new HTMLInputField(self::PARAM_ID,
//					new RequiredValidation()
//				),
//				new HTMLButton('submit', 'Submit', 'submit')
//			),
//			"<br/>"
//		);
//
//		return $Form;
//	}
//
//	// Static
//
//	public static function getRequestURL() {
//		return self::FORM_ACTION;
//	}
//
//	/**
//	 * Route the request to this class object and return the object
//	 * @param IRequest $Request the IRequest inst for this render
//	 * @param array|null $Previous all previous response object that were passed from a handler, if any
//	 * @param null|mixed $_arg [varargs] passed by route map
//	 * @return void|bool|Object returns a response object
//	 * If nothing is returned (or bool[true]), it is assumed that rendering has occurred and the request ends
//	 * If false is returned, this static handler will be called again if another handler returns an object
//	 * If an object is returned, it is passed along to the next handler
//	 */
//	static function routeRequestStatic(IRequest $Request, Array &$Previous = array(), $_arg = null) {
//		return new static();
//	}
//
//	/**
//	 * Handle this request and render any content
//	 * @param IBuildRequest $Request the build request inst for this build session
//	 * @return void
//	 * @build --disable 0
//	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
//	 */
//	static function handleBuildStatic(IBuildRequest $Request) {
//		$RouteBuilder = new RouteBuilder($Request, new SiteMap());
//		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION, __CLASS__, IRequest::NAVIGATION_ROUTE, "Accounts");
//		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION2, __CLASS__, IRequest::NAVIGATION_ROUTE, "Accounts");
//		$RouteBuilder->writeRoute('ANY ' . self::FORM_ACTION3, __CLASS__);
//	}
//}