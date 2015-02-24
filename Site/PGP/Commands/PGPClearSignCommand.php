<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/21/14
 * Time: 3:27 PM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\PGPCommand;
use Site\PGP\PGPCommandResponse;

class PGPClearSignCommand extends PGPCommand
{
	const ALLOW_STD_ERROR = true;
	const CMD             = "--clearsign";

	private $mFileInput;
	private $mKeyID;
	private $mFingerprint;
	private $mSignedString = null;

	public function __construct($signerFingerprint, $fileInput = null) {
		$this->mFileInput = $fileInput;
		$command           = static::CMD;
		if ($fileInput) {
			$command .= ' ' . $fileInput;
		}
		parent::__construct($command);
		$this->appendOption('-o-');
		$this->appendOption('--local-user', $signerFingerprint);
	}

	public function setPassphrase($passphrase) {
		$this->setOption('passphrase', $passphrase);
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @param null $stdIn
	 * @throws Exceptions\PGPCommandException
	 * @throws \Exception
	 * @return PGPCommandResponse the execution response
	 */
	function execute(IRequest $Request=null, $stdIn = null) {
		if($stdIn)
			$this->addSTDIn($stdIn);
		$Response = parent::execute($Request);
		$this->mSignedString = $Response->getOutput();
		return $Response;
	}

	public function getKeyID() {
		return $this->mKeyID;
	}

	public function getFingerprint() {
		return $this->mFingerprint;
	}

	function getSignedString() {
		return $this->mSignedString;
	}
}