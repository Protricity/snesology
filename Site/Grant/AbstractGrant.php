<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/4/14
 * Time: 4:33 PM
 */
namespace Site\Grant;

use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Request\Exceptions\RequestException;
use CPath\Request\IRequest;
use CPath\Request\Log\ILogListener;
use CPath\Response\IResponse;
use Site\Grant\DB\GrantEntry;
use Site\Grant\DB\GrantTable;
use Site\Grant\DB\GrantUserTable;
use Site\Grant\Exceptions\MissingGrantContentException;
use Site\PGP\Commands\PGPChangePassphrase;
use Site\PGP\Commands\PGPDecryptCommand;
use Site\PGP\Commands\PGPDeleteSecretAndPublicKeyCommand;
use Site\PGP\Commands\PGPDetachSignCommand;
use Site\PGP\Commands\PGPEncryptCommand;
use Site\PGP\Commands\PGPExportPrivateKeyCommand;
use Site\PGP\Commands\PGPGenerateKeyPairCommand;
use Site\PGP\Commands\PGPSearchCommand;
use Site\PGP\Commands\PGPVerifyCommand;
use Site\PGP\PGPCommand;
use Site\PGP\PGPConfig;

abstract class AbstractGrant implements ILogListener, IKeyMap
{
	const DIR = __DIR__;

	const KEY_LENGTH = 1024;

	const STORE_ARMORED = true;

	const DEFAULT_NAME = "Unnamed Grant";

	const COMMAND_DELETE = 'delete';
	const COMMAND_TUMBLE = 'tumble';

	const KEYRING_NAME = '.pubring.grant.gpg';

	const FORM_NAME = 'form-grant';
	const FORM_TITLE = 'Grant';

	const PARAM_FINGERPRINT = 'fingerprint';
	const PARAM_CHALLENGE = 'challenge';
	const PARAM_CHALLENGE_PASSPHRASE = 'challenge-answer';
	const PARAM_PASSPHRASE = 'passphrase';
	const PARAM_COMMAND = 'command';

//	const JSON_CONTENT_SIGNATURE = '_sig';

	const KEY_BACKUP = ':backup';
	const JSON_BACKUP = 'backup';
	const JSON_BACKUP_PASSPHRASE = 'backup-passphrase';
	const JSON_BACKUP_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your grant backup.",
		" * If you are reading this, that means you successfully decrypted",
		" * your grant file and may now recover the content.",
		" */"]
}';

	const KEY_PASSPHRASE = ':passphrase';
	const JSON_PASSPHRASE = 'passphrase';
	const JSON_PASSPHRASE_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your decrypted grant passphrase.",
		" * If you are reading this, that means you successfully decrypted",
		" * your grant passphrase and may now execute the granted action.",
		" */"]
}';

	const KEY_PUBLIC = ':public';
	const JSON_ADDRESS = 'address';
	const JSON_PUBLIC_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your grant public data.",
		" * If you are reading this, that means you successfully decrypted",
		" * your grants public information.",
		" */"]
}';

	const KEY_PRIVATE = ':private';
	const JSON_PRIVATE_KEY = 'private_key';
	const JSON_PRIVATE_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your wallet private key.",
		" * If you are reading this, that means you successfully decrypted",
		" * your wallet balance and validated your authority to spend dat bling.",
		" */"]
}';


	private $mGrantFingerprint;
	/** @var ILogListener[] */
	private $mLogListeners = array();

	/**
	 * Execute the granted action with the current passphrase
	 * @param IRequest $Request
	 * @param $passphrase
	 * @internal param $userFingerprint
	 * @internal param HTMLForm $Form
	 * @internal param string $userFingerprint
	 * @return IResponse
	 */
	abstract protected function executeGrantAction(IRequest $Request, $passphrase);

	public function __construct($grantFingerprint) {
		$this->mGrantFingerprint = $grantFingerprint;
	}

	public function getGrantFingerprint($short=false) {
		if($short === false)
			return $this->mGrantFingerprint;
		return substr($this->mGrantFingerprint, -8);
	}

	function getUserFingerprints() {
		$Table = new GrantUserTable();
		$Query = $Table->select($Table::COLUMN_USER_FINGERPRINT)
			->where($Table::COLUMN_GRANT_FINGERPRINT, $this->getGrantFingerprint());
		$users = array();
		while($row = $Query->fetch()) {
			$users[] = $row[$Table::COLUMN_USER_FINGERPRINT];
		}
		return $users;
	}

	/**
	 * @param $content_key
	 * @param bool $throwException
	 * @throws \Exception
	 * @return GrantEntry
	 */
	protected function getGrantEntry($content_key, $throwException=true) {
		$GrantTable = new GrantTable();
		$GrantEntry = $GrantTable
			->where($GrantTable::COLUMN_GRANT_FINGERPRINT, $this->getGrantFingerprint())
			->where($GrantTable::COLUMN_CONTENT_KEY, $content_key)
			->fetch();

		if(!$GrantEntry && $throwException) {
			throw new MissingGrantContentException("Grant content not found: " . $content_key); }

		return $GrantEntry;
	}

	public function getGrantContent($content_key, $throwException=true) {
		$GrantEntry = $this->getGrantEntry($content_key, $throwException);
		$signature = null;
		return $GrantEntry->loadContent($signature);
	}

	public function getPassphraseChallengeContent($armored=true) {
		$GrantEntry = $this->getGrantEntry(static::KEY_PASSPHRASE);
		$content = $GrantEntry->loadContent();
		if($armored)
			$content = PGPCommand::enarmor($content, 'PGP MESSAGE');
		return $content;
	}

	public function tumblePassphrase(IRequest $Request, &$oldPassphrase) {
		$this->verifyPassphrase($Request, $oldPassphrase);

		$newPassphrase = self::generatePassword();

		$PGPChangePassphrase = new PGPChangePassphrase($this->getGrantFingerprint(), $oldPassphrase, $newPassphrase);
		$PGPChangePassphrase->setPrimaryKeyRing(static::KEYRING_NAME);
//		$PGPChangePassphrase->setPassphrase($oldPassphrase);
		$PGPChangePassphrase->execute($Request);

		$this->writePassword($Request, $newPassphrase, $oldPassphrase);

		$this->verifyPassphrase($Request, $newPassphrase);
		$oldPassphrase = $newPassphrase;
	}

	public function encrypt(IRequest $Request, $contentsToEncrypt, $additionalRecipients=null) {
		$additionalRecipients = (array)$additionalRecipients;
		$additionalRecipients[] = $this->getGrantFingerprint();
		$PGPEncrypt = new PGPEncryptCommand($additionalRecipients, $contentsToEncrypt);
		$PGPEncrypt->addKeyRing(static::KEYRING_NAME);
		$PGPEncrypt->addKeyRing(PGPConfig::$KEYRING_USER);
//		if($armored)
//			$PGPEncrypt->setArmored();
		$PGPEncrypt->execute($Request);
		$encrypted = $PGPEncrypt->getEncryptedString();

		return $encrypted;
	}

	public function sign(IRequest $Request, $contentsToSign, $passphrase=null) {
		$PGPSign = new PGPDetachSignCommand($this->getGrantFingerprint(), $contentsToSign);
		$PGPSign->addKeyRing(static::KEYRING_NAME);
//		$PGPSign->addKeyRing(PGPConfig::$KEYRING_USER);
		$PGPSign->setPassphrase($passphrase);
		$PGPSign->execute($Request);
		$signature = $PGPSign->getDetachedSignature();

		return $signature;
	}

	public function verify(IRequest $Request, $contentsToVerify, $signatureData, $passphrase=null) {
		$PGPSign = new PGPVerifyCommand($signatureData, $contentsToVerify);
		$PGPSign->addKeyRing(static::KEYRING_NAME);
		//$PGPSign->addKeyRing(PGPConfig::$KEYRING_USER);
		$PGPSign->setPassphrase($passphrase);
		$PGPSign->execute($Request, $contentsToVerify);

		return $contentsToVerify;
	}

	/**
	 * @param IRequest $Request
	 * @param $contentsToDecrypt
	 * @param null $passphrase
	 * @param null $recipients
	 * @throws RequestException
	 * @return String
	 */
	public function decrypt(IRequest $Request, $contentsToDecrypt, $passphrase=null, &$recipients=null) {

		$PGPDecrypt = new PGPDecryptCommand($contentsToDecrypt);
		$PGPDecrypt->addKeyRing(static::KEYRING_NAME);
		$PGPDecrypt->addKeyRing(PGPConfig::$KEYRING_USER);
		$PGPDecrypt->localUser($this->getGrantFingerprint());
		$PGPDecrypt->setPassphrase($passphrase);
		$PGPDecrypt->execute($Request);
		$recipients = $PGPDecrypt->getEncryptionKeyIDs();
		return $PGPDecrypt->getDecryptedString();
	}

	protected function &checkJSON($json, $key, $errorMessage = null) {
		if(!is_array($json)) {
			throw new \InvalidArgumentException("\$json is not an array"); }
		if(!isset($json[$key]))
			throw new \InvalidArgumentException(($errorMessage ?: "Key not found in JSON") . ": " . $key);
		return $json[$key];
	}

	protected function formatJSONContents($content_key, Array &$json = null) {
		switch($content_key) {
			case static::KEY_PASSPHRASE:
				$this->checkJSON($json, self::JSON_PASSPHRASE);
				$json = json_decode(static::JSON_PASSPHRASE_COMMENTS, true) + $json;
				break;
			case static::KEY_BACKUP:
				$this->checkJSON($json, self::JSON_BACKUP);
				$this->checkJSON($json, self::JSON_BACKUP_PASSPHRASE);
				$json = json_decode(static::JSON_BACKUP_COMMENTS, true) + $json;
				break;
		}
	}

	protected function writePassword(IRequest $Request, $newPassphrase, $oldPassphrase=null) {
		$json = null;
		$recipients = array();
		if($oldPassphrase) {
			$oldJson = $this->decryptGrantContents($Request,  static::KEY_PASSPHRASE, $newPassphrase, $recipients);
			if($oldJson[self::JSON_PASSPHRASE] !== $oldPassphrase) {
				throw new \InvalidArgumentException("Old passphrase mismatch"); }
			$newJSON = $oldJson;
			$newJSON[self::JSON_PASSPHRASE] = $newPassphrase;
			$this->formatJSONContents(static::KEY_PASSPHRASE, $newJSON);

		} else {
			$encryptedGrantContent = $this->getGrantContent(static::KEY_PASSPHRASE, false);
			if($encryptedGrantContent) {
				throw new \InvalidArgumentException("Encrypted grant content already available. Cannot overwrite without old passphrase"); }
			$newJSON = array();
			$newJSON[self::JSON_PASSPHRASE] = $newPassphrase;
			$this->formatJSONContents(static::KEY_PASSPHRASE, $newJSON);
		}

		//$recipients[] = $this->getUserFingerprint();
		//$recipients[] = $this->getGrantFingerprint();
		$this->writeGrantContent($Request, static::KEY_PASSPHRASE, $newJSON, $recipients, $newPassphrase);
	}

	protected function writeGrantContent(IRequest $Request, $content_key, $json, $recipients, $passphrase) {
		if(is_string($json))
			$json = json_decode($json, true);
		if(!is_array($json))
			throw new RequestException("Grant Write Failed. Invalid JSON Array");

		$jsonContent = json_encode($json, JSON_PRETTY_PRINT);

		$recipients = (array)$recipients;
//		$recipients[] = $this->getUserFingerprint();
//		$recipients[] = $this->getGrantFingerprint();
		$recipients = array_unique($recipients);
		$t = microtime(true);
		$encryptedContent = $this->encrypt($Request, $jsonContent, $recipients);
		$signatureContent = $this->sign($Request, $jsonContent, $passphrase);
		$this->verify($Request, $jsonContent, $signatureContent, $passphrase);

		$GrantTable = new GrantTable();
		$GrantEntry = $this->getGrantEntry($content_key, false);
		if(!$GrantEntry) {
			$GrantTable->insert(array(
				$GrantTable::COLUMN_GRANT_FINGERPRINT => $this->getGrantFingerprint(),
				$GrantTable::COLUMN_CONTENT_KEY       => $content_key,
				$GrantTable::COLUMN_CLASS             => get_called_class(),
				$GrantTable::COLUMN_CONTENT           => $encryptedContent,
				$GrantTable::COLUMN_SIGNATURE         => $signatureContent,
			));
			$Request->log(sprintf("Created Grant Entry %s:%s in %.2f seconds", $this->getGrantFingerprint(), $content_key, (microtime(true) - $t)));

		} else {
			$GrantTable
				->update(GrantTable::COLUMN_CONTENT, $encryptedContent)
				->update(GrantTable::COLUMN_SIGNATURE, $signatureContent)
				->where(GrantTable::COLUMN_GRANT_FINGERPRINT, $this->getGrantFingerprint())
				->where(GrantTable::COLUMN_CONTENT_KEY, $content_key)
				->execute();
			$Request->log(sprintf("Updated Grant Entry %s:%s in %.2f seconds", $this->getGrantFingerprint(), $content_key, (microtime(true) - $t)));
		}

		return $encryptedContent;
	}

	public function decryptGrantContents(IRequest $Request, $content_key, $passphrase=null, &$recipients=null) {
		$GrantTable = new GrantTable();
		$Search = $GrantTable
			->where($GrantTable::COLUMN_GRANT_FINGERPRINT, $this->getGrantFingerprint());
		if($content_key)
			$Search
			->where($GrantTable::COLUMN_CONTENT_KEY, $content_key, "LIKE ?");

		$json = array();
		$recipients = array();
		while($GrantEntry = $Search->fetch()) {
			/** @var GrantEntry $GrantEntry */
			$signature = null;
			$content = $GrantEntry->loadContent($signature);
			if(!$content) {
				throw new MissingGrantContentException("Grant entry is missing content: " . $content_key); }

			$decryptedString = $this->decrypt($Request, $content, $passphrase, $recipients);
			$decryptedString = $this->verify($Request, $decryptedString, $signature, $passphrase);

			$j = json_decode($decryptedString, true);
			if(!$j || !is_array($j))
				throw new RequestException("Grant File Decryption Failed. Could not decode JSON: " . $decryptedString);

			foreach($j as $k => $v) {
				if(is_array($v))
					if(isset($json[$k]))
						$v = array_merge((array)$json[$k], $v);
				$json[$k] = $v;
			}
			$json += $j;
		}

		if(sizeof($json) === 0) {
			throw new RequestException("No Grant content found"); }

		return $json;
	}

	public function verifyPassphrase(IRequest $Request, $passphrase) {
		$json = $this->decryptGrantContents($Request, static::KEY_PASSPHRASE, $passphrase);

		if($json[self::JSON_PASSPHRASE] !== $passphrase) {
			throw new RequestException("Passphrase is invalid"); }

		return $json;
	}

	public function getPGPInfo($Request) {
		$Search = new PGPSearchCommand($this->getGrantFingerprint(), '');
		$Search->addKeyRing(static::KEYRING_NAME);
//		$Search->addKeyRing(PGPConfig::$KEYRING_USER);
		return $Search->queryOne($Request);
	}

	public function grantToUser(IRequest $Request, $granteeUserFingerprint, &$passphrase, $grantorUserFingerprint=null) {
		$recipients = array();
		$passphraseJSON = $this->decryptGrantContents($Request,  static::KEY_PASSPHRASE, $passphrase, $recipients);
		foreach($recipients as &$recipient)
			$recipient = substr($recipient, -8);

		if($grantorUserFingerprint) {
			$grantorSecKeyID = $this->getSecretKeyID($Request, $grantorUserFingerprint);
			if (!in_array($grantorSecKeyID, $recipients)) {
				throw new \InvalidArgumentException("Grantor fingerprint not found in grant: " . $granteeUserFingerprint);
			}
		}

		$secKeyID = $this->getSecretKeyID($Request, $granteeUserFingerprint);
		if(in_array($secKeyID, $recipients)) {
			throw new \InvalidArgumentException("User fingerprint already found in grant: " . $granteeUserFingerprint);
		} else {
			$recipients[] = $granteeUserFingerprint;
		}

		$this->writeGrantContent($Request, static::KEY_PASSPHRASE, $passphraseJSON, $recipients, $passphrase);

		$this->tumblePassphrase($Request, $passphrase);
	}

	public function unGrantUser(IRequest $Request, $granteeUserFingerprint, &$passphrase) {
		$recipients = array();
		$passphraseJSON = $this->decryptGrantContents($Request,  static::KEY_PASSPHRASE, $passphrase, $recipients);

		$secKeyID = $this->getSecretKeyID($Request, $granteeUserFingerprint);
		if(!in_array($secKeyID, $recipients)) {
			throw new \InvalidArgumentException("User fingerprint not found in grant: " . $granteeUserFingerprint);
		} else {
			$recipients = array_diff($recipients, array($secKeyID));
		}

		$this->writeGrantContent($Request, static::KEY_PASSPHRASE, $passphraseJSON, $recipients, $passphrase);

		$this->tumblePassphrase($Request, $passphrase);
	}

	private function getSecretKeyID(IRequest $Request, $publicKeyFingerprint) {
		$PGPSearch = new PGPSearchCommand($publicKeyFingerprint, '');
		$PGPSearch->addKeyRing(static::KEYRING_NAME);
		$PGPSearch->addKeyRing(PGPConfig::$KEYRING_USER);
		$PGPSearch = $PGPSearch->queryOne($Request);
		return $PGPSearch->getSubShortCode();
	}

	function addUser(IRequest $Request, $user_fingerprint) {
		$Table = new GrantUserTable();
		$Table->insert(array(
			$Table::COLUMN_GRANT_FINGERPRINT => $this->getGrantFingerprint(),
			$Table::COLUMN_USER_FINGERPRINT => $user_fingerprint,
		))->execute($Request);
	}

	function removeUser(IRequest $Request, $user_fingerprint) {
		$Table = new GrantUserTable();
		$Table->delete($Table::COLUMN_GRANT_FINGERPRINT, $this->getGrantFingerprint())
			->where($Table::COLUMN_USER_FINGERPRINT, $user_fingerprint)
			->execDelete($Request, true);
	}

	/**
	 * Add a log entry
	 * @param mixed $msg The log message
	 * @param int $flags [optional] log flags
	 * @return int the number of listeners that processed the log entry
	 */
	function log($msg, $flags = 0) {
		$c = 0;
		foreach($this->mLogListeners as $Log)
			$c += $Log->log($msg, $flags);
		return $c;
	}

	/**
	 * Add a log listener callback
	 * @param ILogListener $Listener
	 * @return void
	 * @throws \InvalidArgumentException if this log listener inst does not accept additional listeners
	 */
	function addLogListener(ILogListener $Listener) {
		if(!in_array($Listener, $this->mLogListeners))
			$this->mLogListeners[] = $Listener;
	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		$Map->map('fingerprint', $this->getGrantFingerprint());
		$Map->map('users', implode(', ', $this->getUserFingerprints()));
	}

	// Static

	static private function generatePassword() {
		$newPassphrase = md5(uniqid(time(), true));
		return $newPassphrase;
	}

//	protected static function generateNewGrantID(IRequest $Request, $userFingerprint) {
//		$userFingerprint = substr($userFingerprint, -8);
//		$grantID = basename(get_called_class()) . ':' . $userFingerprint;
//		return $grantID;
//	}

	/**
	 * @param $userFingerprints
	 * @param $className
	 * @return AbstractGrant[]
	 */
	public static function search($userFingerprints, $className = null) {
		$GrantTable = new GrantTable();
		$GrantUserTable = new GrantUserTable();
		$SelectGrantID = $GrantUserTable
			->select('DISTINCT ' . $GrantUserTable::COLUMN_GRANT_FINGERPRINT)
			->where($GrantUserTable::COLUMN_USER_FINGERPRINT, $userFingerprints);

		$SelectGrant = $GrantTable->select($GrantTable::COLUMN_CLASS . ', ' . $GrantTable::COLUMN_GRANT_FINGERPRINT)
			->where($GrantTable::COLUMN_GRANT_FINGERPRINT, $SelectGrantID);
		$className === false ?: $className = get_called_class();
		if($className !== false)
			$SelectGrant->where($GrantTable::COLUMN_CLASS, $className);

		$Grants = array();

		while($row = $SelectGrant->fetch(\PDO::FETCH_NUM)) {
			list($className, $grantFingerprint) = $row;
			$key = $className . ':' . $grantFingerprint;
			if(isset($Grants[$key]))
				continue;
			$Grants[$key] = new $className($grantFingerprint);
		}
		return array_values($Grants);
	}

	public static function create(IRequest $Request, $recipients, $grantID, &$newPassphrase = null) {
		is_array($recipients) ?: $recipients = (array)$recipients;
		$newPassphrase = self::generatePassword();

		$t = microtime(true);

		$Request->log(sprintf("Generating Grant %s...", $grantID), $Request::VERBOSE);
		$PGPCreate = new PGPGenerateKeyPairCommand($grantID, array(
			PGPGenerateKeyPairCommand::ARG_COMMENT => get_called_class(),
			PGPGenerateKeyPairCommand::ARG_KEY_LENGTH => static::KEY_LENGTH,
		));
		$PGPCreate->setPrimaryKeyRing(static::KEYRING_NAME);
		$PGPCreate->execute($Request);

		$GrantSearch = $PGPCreate->getSearchCommand();
		$GrantSearch = $GrantSearch->queryFirst($Request);

		$Grant = new static($GrantSearch->getFingerprint());
		$Request->log(sprintf("Grant Generated in %.2f seconds: %s", microtime(true) - $t, $grantID));
		foreach($recipients as $recipient)
			$Grant->addUser($Request, $recipient);
		$recipients[] = $Grant->getGrantFingerprint();

		$PGPChangePassphrase = new PGPChangePassphrase($Grant->getGrantFingerprint(), $newPassphrase, $newPassphrase);
		$PGPChangePassphrase->addKeyRing(static::KEYRING_NAME);
		$PGPChangePassphrase->execute($Request);

//		$Grant->tumblePassphrase($Request, $newPassphrase);


		$json = array();
		$json[self::JSON_PASSPHRASE] = $newPassphrase;
		$Grant->formatJSONContents(static::KEY_PASSPHRASE, $json);
		$Grant->writeGrantContent($Request, static::KEY_PASSPHRASE, $json, $recipients, $newPassphrase);

		$Grant->verifyPassphrase($Request, $newPassphrase);

		$PGPExportPrivateKeyCommand = new PGPExportPrivateKeyCommand($Grant->getGrantFingerprint(), false);
		$PGPExportPrivateKeyCommand->setPrimaryKeyRing(static::KEYRING_NAME);
		$PGPExportPrivateKeyCommand->execute($Request);
		$exportedPrivateKey = $PGPExportPrivateKeyCommand->getExportedString();

		$json = array();
		$json[self::JSON_BACKUP_PASSPHRASE] = $newPassphrase;
		$json[self::JSON_BACKUP] = base64_encode($exportedPrivateKey);

		$Grant->formatJSONContents(static::KEY_BACKUP, $json);
		$Grant->writeGrantContent($Request, static::KEY_BACKUP, $json, $recipients, $newPassphrase);

		return $Grant;
	}

	protected static function deleteGrant(IRequest $Request, AbstractGrant $Grant, $passphrase) {
		try {
			$Grant->verifyPassphrase($Request, $passphrase);
		} catch (MissingGrantContentException $ex) {
			// Ok to delete grants with no passphrase
		}

		$PGPSearch = new PGPSearchCommand($Grant->getGrantFingerprint(), '');
		$PGPSearch->setPrimaryKeyRing(static::KEYRING_NAME);
		$PGPSearch = $PGPSearch->queryOne($Request);

		$PGPDelete = new PGPDeleteSecretAndPublicKeyCommand($PGPSearch->getFingerprint());
		$PGPDelete->setPrimaryKeyRing(static::KEYRING_NAME);
		$PGPDelete->execute($Request);

		$GrantTable = new GrantTable();
		$GrantTable->delete($GrantTable::COLUMN_GRANT_FINGERPRINT, $Grant->getGrantFingerprint())
			->execDelete($Request, true);
		$GrantUserTable = new GrantUserTable();
		$GrantUserTable->delete($GrantTable::COLUMN_GRANT_FINGERPRINT, $Grant->getGrantFingerprint())
			->execDelete($Request, true);
	}
}

