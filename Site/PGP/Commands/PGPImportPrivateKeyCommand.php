<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/18/2014
 * Time: 5:17 PM
 */
namespace Site\PGP\Commands;

use CPath\Data\Map\IKeyMapper;
use CPath\Request\IRequest;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Exceptions\PGPKeyAlreadyImported;
use Site\PGP\PGPCommand;

class PGPImportPrivateKeyCommand extends PGPCommand
{
	const INCLUDE_STDERR = false;
	const ALLOW_STD_ERROR = true;
	const CMD = "--command-fd 0 --batch --allow-secret-key-import --import";
	private $mKeyID;
	public function __construct($privateKeyString) {
		$this->addSTDIn($privateKeyString);
		$command           = static::CMD;
		parent::__construct($command);
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws Exceptions\PGPCommandException
	 * @throws PGPKeyAlreadyImported
	 * @throws \Exception
	 * @return PGPImportPrivateKeyCommand
	 */
	function execute(IRequest $Request=null) {
		$Response = parent::execute($Request);

		$Exs = $Response->getExceptions();
		if($Exs)
			throw $Exs[0];

		if (preg_match('/gpg: key (\w+): already in keyring/i', $Response->getSTDErr(), $matches))
			throw new PGPKeyAlreadyImported($this, $matches[1], "Key already imported: " . $matches[1]);

//		if (preg_match('/gpg: key (\w+): "([^"]+)" not changed/i', $Response->getSTDErr(), $matches))
//			throw new PGPKeyAlreadyImported($this, $matches[1], "Key already imported: " . $matches[2] . ' (' . $matches[1] . ')');

		if (!preg_match('/gpg: key (\w+): (?:private|secret) key (?:"([^"]+)" )?imported/i', $Response->getSTDErr(), $matches))
			throw new PGPCommandException($this, "Private GPG key failed to import: " . ($Response->getSTDErr() ?: 'No Error Provided'));

		$this->mKeyID = $matches[1];
		return $this->update("Private Key imported successfully: " . $this->mKeyID);
	}

	public function getKeyID() {
		return $this->mKeyID;
	}
//
//	public function exportPrivateKey() {
//		return new PGPExportPrivateKeyCommand($this->getKeyID());
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