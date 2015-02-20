<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/18/14
 * Time: 8:49 AM
 */
namespace Site\PGP\Commands;

use Site\PGP\PGPCommand;

class PGPDecryptCommand extends PGPCommand
{
	const SIGN_ATTR_KEY_ID = 'key';
	const SIGN_ATTR_DATE = 'date';
	const SIGN_ATTR_UNIXTIME = 'unix_time';

	const DEC_ATTR_KEY_ID = 'key';
	const DEC_ATTR_ID_STRING = 'id_string';

	const INCLUDE_STDERR = false;

	const ALLOW_STD_ERROR = true;
	const CMD = " --no-tty --command-fd 0 --decrypt";

	public function __construct($decryptString) {
		$command                  = self::CMD;
		parent::__construct($command);
		$this->addSTDIn($decryptString);
	}

//
//	function execute(IRequest $Request = null) {
//		$Response = parent::execute($Request);
//		return $Response;
//
//	}

	function getDecryptedString() {
		return $this->getOutputString();
	}

	public function getEncryptionKeyIDs() {
		$ids = $this->getCommandResponse()->getEncryptionKeyIDs();
		if(!$ids)
			throw new \Exception("No encryption ids parsed");
		return $ids;
	}

	public function getSignIDs() {
		return $this->getCommandResponse()->getSignIDs();
	}

	public function getUserIDs() {
		return $this->getCommandResponse()->getUserIDs();
	}
}

