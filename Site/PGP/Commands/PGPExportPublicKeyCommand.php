<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/18/14
 * Time: 9:42 AM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\PGP\PGPCommand;

class PGPExportPublicKeyCommand extends PGPCommand
{

	const ALLOW_STD_ERROR = false;
	const CMD             = "--export %s";
	//const CMD = "%s --export-secret-keys --armor %s";

	private $mExportedString = null;

	public function __construct($fingerprint, $armored=true) {
		$command       = sprintf(static::CMD, $fingerprint);
		parent::__construct($command);
		if($armored)
			$this->setArmored();
	}

	public function setArmored() {
		$this->appendOption('armor');
	}

	function execute(IRequest $Request=null) {
		$Response = parent::execute($Request);

		if ($Exs = $Response->getExceptions())
			throw $Exs[0];

		$this->mExportedString = $Response->getOutput();

		$Response->update("Key exported successfully", true);
		return $Response;
	}

	function getExportedString() {
		return $this->mExportedString;
	}
}