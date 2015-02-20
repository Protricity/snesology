<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/24/2014
 * Time: 5:12 PM
 */
namespace Site\PGP\Commands;

use Site\PGP\PGPCommand;

class PGPDetachSignCommand extends PGPCommand
{
	const CMD = " --trust-model always --batch --detach-sign";

	public function __construct($signer, $contentToSign) {
		parent::__construct(self::CMD);
		$this->localUser($signer);
		$this->addSTDIn($contentToSign);
	}

	function getDetachedSignature() {
		return $this->getOutputString();
	}

}