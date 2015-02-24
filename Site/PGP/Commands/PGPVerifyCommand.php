<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/21/14
 * Time: 3:27 PM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\PGPCommand;

class PGPVerifyCommand extends PGPCommand
{
	const INCLUDE_STDERR = true;
	const CMD             = "--trust-model always --batch --verify";

	private $mRequireSignatureID;
	private $mSignatureData;
	public function __construct($signatureData, $contentsToVerify, $signatureID=null) {
		$this->mSignatureData = $signatureData;
		parent::__construct(self::CMD);
		$this->addSTDIn($contentsToVerify);
		$this->mRequireSignatureID = $signatureID;
	}

	function execute(IRequest $Request) {
		$tmpFile = tmpfile();
		$info    = stream_get_meta_data($tmpFile);
		$tmpPath = $info['uri'];
		fwrite($tmpFile, $this->mSignatureData);
		$this->setCommand(self::CMD . ' ' . $tmpPath . ' - ');

		$Response = parent::execute($Request);

		$Exs = $Response->getExceptions();
		if ($Exs)
			throw $Exs[0];

		$stdErr = $Response->getOutput();
		if(!preg_match('/Signature made .* using .* key ID (\w+)/i', $stdErr, $matches)) {
			throw new PGPCommandException($this, "Signature not found: " . $stdErr);}
		$keyID = trim($matches[1]);

		if(preg_match('/BAD signature from "(.*)"/i', $stdErr, $matches)) {
			throw new PGPCommandException($this, $stdErr);}
		if(!preg_match('/Good signature from "(.*)"/i', $stdErr, $matches)) {
			throw new PGPCommandException($this, "Required signature not found");}
		$this->log($matches[0], $this::VERBOSE);
		if($this->mRequireSignatureID && strpos($this->mRequireSignatureID, $keyID) === false) {
			throw new PGPCommandException($this, "Required signature mismatch");}
	}


}