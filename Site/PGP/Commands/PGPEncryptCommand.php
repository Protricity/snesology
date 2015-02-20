<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/18/14
 * Time: 9:17 AM
 */
namespace Site\PGP\Commands;

use Site\PGP\PGPCommand;

class PGPEncryptCommand extends PGPCommand
{
	const CMD             = " --trust-model always --batch --encrypt";
	const INCLUDE_STDERR = false;

	public function __construct($recipients, $stringToEncrypt) {
		parent::__construct(self::CMD);
		foreach ((array)$recipients as $recipient)
			$this->addRecipient($recipient);
		$this->addSTDIn($stringToEncrypt);
	}

	function getEncryptedString() {
		$Exs = $this->getCommandResponse()->getExceptions();
		if ($Exs) {
			throw $Exs[0]; }
		return $this->getOutputString();
	}

}

