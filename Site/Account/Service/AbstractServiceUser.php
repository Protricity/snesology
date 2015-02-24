<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/18/2014
 * Time: 2:38 PM
 */
namespace Site\Account\Service;

use CPath\Request\IRequest;
use Site\PGP\Commands\Exceptions\PGPNotFoundException;
use Site\PGP\Commands\PGPDecryptCommand;
use Site\PGP\Commands\PGPDeleteSecretAndPublicKeyCommand;
use Site\PGP\Commands\PGPDetachSignCommand;
use Site\PGP\Commands\PGPEncryptCommand;
use Site\PGP\Commands\PGPExportPrivateKeyCommand;
use Site\PGP\Commands\PGPExportPublicKeyCommand;
use Site\PGP\Commands\PGPImportPrivateKeyCommand;
use Site\PGP\Commands\PGPSearchCommand;
use Site\PGP\Commands\PGPVerifyCommand;
use Site\PGP\PGPCommand;
use Site\PGP\PGPConfig;

abstract class AbstractServiceUser extends User
{
	const KEY_LENGTH = 240;

	private $mPassphrase = null;
	private $mUserID;
	public function __construct(IRequest $Request, $userID, $passphrase=null) {
		$this->mUserID = $userID;
		$this->mPassphrase = $passphrase;
		try {
			$User = User::searchFirst($Request, $userID);

		} catch (PGPNotFoundException $ex) {
			$this->createAccount($Request);
//			$ids = $Create->getKeyIDs();
//			$User = new User($ids[0]);
			$User = User::searchFirst($Request, $userID);
		}
		parent::__construct($User->getFingerprint(), $User->getUserID());
	}

	public function getUserID(IRequest $Request=null) {
		return $this->mUserID;
	}

	protected function getPassphrase() {
		return $this->mPassphrase;
	}

	protected function setCommand(PGPCommand $CMD) {
		$CMD->setPrimaryKeyRing(PGPConfig::$KEYRING_USER);
//		$CMD->addKeyRing(PGPConfig::$KEYRING_GRANT);
		if($this->getPassphrase())
			$CMD->setPassphrase($this->getPassphrase());
	}

	public function decrypt(IRequest $Request, $stringToDecrypt) {
		$CMD = new PGPDecryptCommand($stringToDecrypt);
		$this->setCommand($CMD);
		$CMD->execute($Request);
		return $CMD->getDecryptedString();
	}

	public function encrypt(IRequest $Request, $stringToEncrypt, $additionalRecipients=null) {
		$additionalRecipients = (array)$additionalRecipients;
		$additionalRecipients[] = $this->getFingerprint();
		$CMD = new PGPEncryptCommand($additionalRecipients, $stringToEncrypt);
		$this->setCommand($CMD);
		$CMD->execute($Request);
		return $CMD->getEncryptedString();
	}

	public function solveChallengePassphrase(IRequest $Request, AbstractGrant $Grant) {
		$challenge = $Grant->getPassphraseChallengeContent(false);
		$json = $this->decrypt($Request, $challenge);
		$json = json_decode($json, true);
		$passphrase = $json[$Grant::JSON_PASSPHRASE];
		return $passphrase;
	}

	public function sign(IRequest $Request, $stringToSign) {
		$PGPSign = new PGPDetachSignCommand($this->getFingerprint(), $stringToSign);
		$PGPSign->addKeyRing(PGPConfig::$KEYRING_USER);
		$this->setCommand($PGPSign);
		$PGPSign->execute($Request);
		return $PGPSign->getDetachedSignature();
	}

	public function verify($Request, $card, $signedCard) {
		$CMD = new PGPVerifyCommand($signedCard, $card);
		$this->setCommand($CMD);
		$CMD->execute($Request);
		return $card;
	}

	public function hasAccount(IRequest $Request=null) {
		$UserSearch = new PGPSearchCommand($this->getUserID(), '=');
		$UserSearch->addKeyRing(PGPConfig::$KEYRING_USER);
		$All = $UserSearch->queryAll($Request);
		return sizeof($All) > 0;
	}

	public function deleteAccount(IRequest $Request=null) {
		$UserSearch = new PGPSearchCommand($this->getUserID(), '=');
		$UserSearch->addKeyRing(PGPConfig::$KEYRING_USER);
		$All = $UserSearch->queryAll($Request);
		if(sizeof($All) === 0) {
			throw new \InvalidArgumentException("Can not delete an account that doesn't exist"); }
		if(sizeof($All) > 1)
			throw new \InvalidArgumentException("Multiple accounts exist for user: " . $this->getUserID());

		$PGPDelete = new PGPDeleteSecretAndPublicKeyCommand($All[0]->getFingerprint());
		$this->setCommand($PGPDelete);
		$Response = $PGPDelete->execute($Request);

		return $Response;
	}

	public function createAccount(IRequest $Request=null) {
		if($this->hasAccount($Request))
			throw new \InvalidArgumentException("Can not create account. User already exists: " . $this->getUserID());
		$Create = new CreateUser($this->getUserID(), null, static::KEY_LENGTH);
		$Response = $Create->execute($Request);

//		$PGPCheckTrustDB = new PGPCheckTrustDB();
//		$PGPCheckTrustDB->execute($Request);

		return $Response;
	}

	public function exportPublicKey(IRequest $Request, $armored=true) {
		$PGPExport = new PGPExportPublicKeyCommand($this->getFingerprint(), $armored);
		$this->setCommand($PGPExport);
		$PGPExport->execute($Request);
		return $PGPExport->getExportedString();
	}

	public function exportPrivateKey(IRequest $Request, $armored=true) {
		$PGPExport = new PGPExportPrivateKeyCommand($this->getFingerprint(), $armored);
		$this->setCommand($PGPExport);
		$PGPExport->execute($Request);
		return $PGPExport->getExportedString();
	}

	public function importPrivateKey(IRequest $Request, $privateKeyString) {
		$PGPImport = new PGPImportPrivateKeyCommand($privateKeyString);
		$this->setCommand($PGPImport);
		$PGPImport->execute($Request);
		$keyID = $PGPImport->getKeyID();

		$UserSearch = new PGPSearchCommand($keyID, '');
		$UserSearch->addKeyRing(PGPConfig::$KEYRING_USER);
		$All = $UserSearch->queryAll($Request);
		if(sizeof($All) === 0)
			throw new \InvalidArgumentException("Could not import private key - User not found after: " . $keyID);
		if(sizeof($All) > 1)
			throw new \InvalidArgumentException("Multiple accounts exist for user: " . $this->getUserID());
		return $All[0];
	}
}