<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/24/2014
 * Time: 4:36 PM
 */
namespace Site\PGP\Commands;

use Site\PGP\PGPCommand;

class PGPSignCommand extends PGPCommand
{
	const CMD = " --trust-model always --batch --sign";

	public function __construct($signer, $stringToSign) {
		parent::__construct(self::CMD);
		$this->localUser($signer);
		$this->addSTDIn($stringToSign);
	}

	public function getSignedContent() {
		return $this->getOutputString();
	}
}
