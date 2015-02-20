<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/15/14
 * Time: 8:11 PM
 */
namespace Site\PGP;

use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Request\IRequest;

class PGPSupportHeaders implements IHTMLSupportHeaders
{

	/**
	 * Write all support headers used by this renderer
	 * @param IRequest $Request
	 * @param \CPath\Render\HTML\Header\IHeaderWriter $Head the writer inst to use
	 * @return void
	 */
	function writeHeaders(IRequest $Request, IHeaderWriter $Head) {
//		$Head->writeScript(__DIR__ . '/assets/libs/openpgpjs-dist/openpgp.min.js');
		$Head->writeScript(__DIR__ . '/assets/libs/openpgpjs-dist/openpgp.js');

		//$Head->writeScript(HeaderConfig::$RequireJSPath);
		//$Head->writeScript(__DIR__ . '/libs/openpgpjs/src/openpgp.js');
//		HeaderConfig::writeJQueryHeadersOnce($Head);
//		$Head->writeScript(__DIR__ . '/libs/polyfill.js');
//		$Head->writeScript(__DIR__ . '/libs/crypto.getRandomValues/fortuna.js');
//		$Head->writeScript(__DIR__ . '/libs/crypto.getRandomValues/crypto.getRandomValues.js');
		//$worker = __DIR__ . '/assets/libs/openpgpjs/dist/openpgp.worker.min.js';
//		$Head->writeScript(__DIR__ . '/assets/libs/openpgpjs/dist/openpgp.worker.min.js');
	}
}