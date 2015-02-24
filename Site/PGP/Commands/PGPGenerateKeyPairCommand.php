<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/18/14
 * Time: 9:35 AM
 */
namespace Site\PGP\Commands;

use CPath\Request\IRequest;
use Site\Config;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Commands\Exceptions\PGPKeyBlockException;
use Site\PGP\PGPCommand;
use Site\PGP\PGPConfig;

class PGPGenerateKeyPairCommand extends PGPCommand
{
//	const MOCK_GEN_KEY = ':mock-gpg-gen-key';

	const ARG_NAME        = 'Name-Real';
	const ARG_EMAIL       = 'Name-Email';
	const ARG_COMMENT     = 'Name-Comment';
//	const ARG_PASSPHRASE  = 'Passphrase';
	const ARG_EXPIRE_DATE = 'Expire-Date';

	const ARG_KEY_TYPE      = 'Key-Type';
	const ARG_KEY_LENGTH    = 'Key-Length';
	const ARG_SUBKEY_TYPE   = 'Subkey-Type';
	const ARG_SUBKEY_LENGTH = 'Subkey-Length';

	const PASSTHROUGH = true;

	const INCLUDE_STDERR = true;
	const ALLOW_STD_ERROR = true;
	const CMD             = "--batch --gen-key";
	private $mArgs;
	private $mKeyID = null;

	public function __construct($username, Array $args = array()) {
		if (!$username)
			throw new \InvalidArgumentException("No username entered for key pair generation");

		$args = array(
			'%echo Generating a basic OpenPGP key',
			self::ARG_KEY_TYPE      => 'DSA',
			self::ARG_KEY_LENGTH    => Config::$DefaultKeySize,
			self::ARG_SUBKEY_TYPE   => 'ELG-E',
			self::ARG_SUBKEY_LENGTH  => null,
			self::ARG_NAME          => $username, // 'Joe Tester',
			self::ARG_COMMENT       => null, // 'with stupid passphrase',
			self::ARG_EMAIL         => null, // $email, // 'joe@foo.bar',
			self::ARG_EXPIRE_DATE   => 0,
//			self::ARG_PASSPHRASE    => null,
			//    '%pubring foo.pub',
			//    '%secring foo.sec',
			'%commit',
			//    '%echo done',
		);

		foreach ($args as $key => $value)
			$args[$key] = $value;
//
//		if ($passphase !== null)
//			$args[self::ARG_PASSPHRASE] = $passphase;

		if(empty($args[self::ARG_SUBKEY_LENGTH]))
			$args[self::ARG_SUBKEY_LENGTH] = $args[self::ARG_KEY_LENGTH];
		$argList = '';
		foreach ($args as $key => $arg)
			if (is_int($key))
				$argList .= ($argList ? "\n" : null) . $arg;
			elseif ($arg !== null)
				$argList .= ($argList ? "\n" : null) . $key . ": " . $arg;

		$this->addSTDIn($argList);

		parent::__construct(static::CMD);

		if(PGPConfig::$HomeDir)
			$this->setHomeDir(PGPConfig::$HomeDir);
	}

	/**
	 * Execute a command and return a response. Does not render
	 * @param IRequest $Request
	 * @throws PGPCommandException
	 * @throws PGPKeyBlockException
	 * @throws \Exception
	 * @return PGPGenerateKeyPairCommand the execution response
	 */
	function execute(IRequest $Request=null) {
		$this->log("Generating new key pair...");
		$Response = parent::execute($Request);
		$Exs = $Response->getExceptions();
		if ($Exs)
			throw $Exs[0];

		$trustedIDs = $Response->getTrustedKeyIDs();
		if (sizeof($trustedIDs) === 0)
			throw new PGPCommandException($this, "Key ID couldn't be determined from stderr: " . $this->getSTDOut());

		return $this->update("Generated PGP key pair successfully: " . $this->mKeyID);
	}

	public function getKeyIDs() {
		return $this->getCommandResponse()->getTrustedKeyIDs();
	}

	/**
	 * @return PGPSearchCommand
	 */
	public function getSearchCommand() {
		$ids = $this->getKeyIDs();
		$CMD = new PGPSearchCommand($ids[0], '');

		if($homeDir = $this->getOption(self::OPTION_HOMEDIR))
			$CMD->setHomeDir($homeDir);

		if($primaryKeyRing = $this->getOption(self::OPTION_PRIMARY_KEYRING))
			$CMD->setPrimaryKeyRing($primaryKeyRing);

		if($keyRings = $this->getOption(self::OPTION_KEYRING))
			$CMD->addKeyRing($keyRings);
		return $CMD;
	}
//
//	public function exportPublicKey() {
//		$CMD = new PGPExportPublicKeyCommand('=' . $this->getKeyID());
//
//		if($homeDir = $this->getOption(self::OPTION_HOMEDIR))
//			$CMD->setHomeDir($homeDir);
//
//		if($primaryKeyRing = $this->getOption(self::OPTION_PRIMARY_KEYRING))
//			$CMD->setPrimaryKeyRing($primaryKeyRing);
//
//		if($keyRings = $this->getOption(self::OPTION_KEYRING))
//			$CMD->addKeyRing($keyRings);
//		return $CMD;
//	}
//
//	public function exportPrivateKey() {
//		$CMD = new PGPExportPrivateKeyCommand('=' . $this->getKeyID());
//
//		if($homeDir = $this->getOption(self::OPTION_HOMEDIR))
//			$CMD->setHomeDir($homeDir);
//
//		if($primaryKeyRing = $this->getOption(self::OPTION_PRIMARY_KEYRING))
//			$CMD->setPrimaryKeyRing($primaryKeyRing);
//
//		if($keyRings = $this->getOption(self::OPTION_KEYRING))
//			$CMD->addKeyRing($keyRings);
//		return $CMD;
//	}

//	/**
//	 * Map data to the key map
//	 * @param IKeyMapper $Map the map inst to add data to
//	 * @internal param \CPath\Request\IRequest $Request
//	 * @internal param \CPath\Request\IRequest $Request
//	 * @return void
//	 */
//	function mapKeys(IKeyMapper $Map) {
//		parent::mapKeys($Map);
//		$Map->map('pgp', $this->mEntry);
//		$Map->map('fingerprint', $this->getFingerprint());
//	}
}