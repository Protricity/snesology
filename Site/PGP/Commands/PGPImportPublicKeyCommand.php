<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/16/14
 * Time: 7:15 PM
 */
namespace Site\PGP\Commands;

use CPath\Data\Map\IKeyMapper;
use CPath\Request\IRequest;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Exceptions\PGPKeyAlreadyImported;
use Site\PGP\PGPCommand;

class PGPImportPublicKeyCommand extends PGPCommand
{
	const ALLOW_STD_ERROR = true;
	const CMD = "--command-fd 0 --batch --import";
	private $mKeyID;

	public function __construct($armoredPublicKey) {
		$this->addSTDIn($armoredPublicKey);
		$command           = static::CMD;
		parent::__construct($command);
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws Exceptions\PGPCommandException
	 * @throws PGPKeyAlreadyImported
	 * @throws \Exception
	 * @return PGPImportPublicKeyCommand
	 */
	function execute(IRequest $Request=null) {
		$Response = parent::execute($Request);

		$Exs = $Response->getExceptions();
		if($Exs)
			throw $Exs[0];

		if (preg_match('/gpg: key (\w+): already in keyring/i', $Response->getSTDErr(), $matches))
			throw new PGPKeyAlreadyImported($this, $matches[1], "Key already imported: " . $matches[1]);

		if (preg_match('/gpg: key (\w+): "([^"]+)" not changed/i', $Response->getSTDErr(), $matches))
			throw new PGPKeyAlreadyImported($this, $matches[1], "Key already imported: " . $matches[2] . ' (' . $matches[1] . ')');

		if (!preg_match('/gpg: key (\w+): public key "([^"]+)" imported/i', $Response->getSTDErr(), $matches))
			throw new PGPCommandException($this, "Public GPG key failed to import: " . ($Response->getSTDErr() ?: 'No Error Provided'));

		$this->mKeyID = $matches[1];
		return $this->update("Public Key imported successfully: " . $this->mKeyID);
	}

	public function getKeyID() {
		return $this->mKeyID;
	}

//	public function exportPublicKey() {
//		return new PGPExportPublicKeyCommand($this->getKeyID());
//	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		parent::mapKeys($Map);
		$Map->map('keyID', $this->getKeyID());
	}
}
