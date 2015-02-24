<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/16/14
 * Time: 7:06 PM
 */
namespace Site\PGP;

use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Response\IResponse;
use CPath\Response\Response;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Commands\Exceptions\PGPKeyBlockException;

class PGPCommandResponse extends Response implements IKeyMap
{
	private $mCommand;
	private $mReturnValue;
	private $mSTDErr;

	public function __construct(PGPCommand $Command, $returnValue, $stdErr) {
		$this->mCommand     = $Command;
		$this->mReturnValue = $returnValue;
		$this->mSTDErr      = $stdErr;
		parent::__construct();
	}

	/**
	 * @return PGPCommand
	 */
	public function getCommand() {
		return $this->mCommand;
	}

	/**
	 * @return mixed
	 */
	public function getOutput() {
		return $this->mCommand->getOutputString();
	}

	/**
	 * @return mixed
	 */
	public function getSTDErr() {
		return $this->mSTDErr ?: $this->mCommand->getSTDOut();
	}

	/**
	 * @return int
	 */
	public function getReturnValue() {
		return $this->mReturnValue;
	}

	public function getExceptions() {
		$Exs = array();

		$stdErr = $this->getSTDErr();

        if (preg_match('/is not recognized/i', $stdErr, $matches))
            $Exs[] = new PGPCommandException($this->mCommand, $stdErr);

        if (preg_match('/gpg: can\'t do this in batch mode/i', $stdErr, $matches))
            $Exs[] = new PGPCommandException($this->mCommand, $stdErr);

        if (preg_match('/encryption failed/i', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, $stdErr);

//		if (preg_match('/gpg: .*failed/i', $stdErr, $matches))
//			$Exs[] = new PGPCommandException($this->mCommand, $stdErr);

		if (preg_match('/gpg: cannot open tty/i', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, $stdErr);

		if (preg_match('/gpg: error reading key: public key not found/i', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, $stdErr);

		if (preg_match('/gpg: WARNING: nothing exported/i', $stdErr, $matches)) {
			$Exs[] = new PGPCommandException($this->mCommand, $stdErr); }

		if (preg_match('/gpg: decryption failed: No secret key/i', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, $stdErr);

		if (preg_match('/^gpg: Can\'t check signature: No public key/im', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, "Could not verify signature: No public key found");

		if (preg_match('/^gpg: no signed data$/i', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, "No signed data");

		if (preg_match('/key "(.*)" not found: (.*)/i', $stdErr, $matches))
			$Exs[] = new PGPCommandException($this->mCommand, "Key not found");

		if (preg_match('/gpg: keyblock resource (.*)/i', $stdErr, $matches))
			$Exs[] = new PGPKeyBlockException($this->mCommand, $stdErr);

		return $Exs;
	}

	private function append(Array &$arr, $val) {
		foreach($arr as &$v) {
			if (strpos($v, $val) === 0)
				return;

			if (strpos($val, $v) === 0) {
				$v = $val;
				return;
			}
		}
		$arr[] = $val;
	}

	public function getTrustedKeyIDs() {
		$ids = array();
		$stdErr = $this->getSTDErr();
		if (preg_match_all('/gpg: key (\w+) marked as ultimately trusted/i', $stdErr, $matches)) {
			foreach ($matches[1] as $i => $keyID) {
				$this->append($ids, $keyID);
			}
		}
		return $ids;
	}

	public function getEncryptionKeyIDs() {
		$ids = array();
		$stdErr = $this->getSTDErr();
		if (preg_match_all('/gpg: encrypted with .*ID (\w+)(?:,.*)?/i', $stdErr, $matches)) {
			foreach ($matches[1] as $i => $keyID) {
				$this->append($ids, $keyID);
			}
		}
		if(preg_match_all('/^Primary key fingerprint: (.*)$/i', $stdErr, $matches)) {
			foreach ($matches[1] as $i => $keyID) {
				$this->append($ids, $keyID);
			}
		}

		return $ids;
	}

	public function getSignIDs() {
		$ids = array();
		$stdErr = $this->getSTDErr();
		if (preg_match_all('/gpg: Signature made (.*) using (RSA|DSA) key ID (\w+)\s*$/im', $stdErr, $matches)) {
			foreach ($matches[3] as $i => $keyID) {
				$this->append($ids, $keyID);
			}
		}

		return $ids;
	}

	public function getUserIDs() {
		$userIDs = array();
		$stdErr = $this->getSTDErr();
		if(preg_match_all('/^gpg: Good signature from "([^"]+)" \[([^\]]+)\]\s*$/im', $stdErr, $matches)) {
			foreach ($matches[1] as $i => $keyID) {
				$this->append($userIDs, $keyID);
			}
		}
		return $userIDs;
	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		$Map->map(IResponse::STR_MESSAGE, $this->getMessage());
		$Map->map(IResponse::STR_CODE, $this->getCode());
		$Map->map('output', $this->getOutput());
		$Map->map('return', $this->getReturnValue());
		$Map->map('stderr', $this->getSTDErr());
		$Map->map('command', $this->getCommand()->getCommand(true));
	}
}