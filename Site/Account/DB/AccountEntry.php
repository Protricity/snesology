<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Account\DB;
use CPath\Framework\Data\Serialize\Interfaces\ISerializable;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Request\Validation\Exceptions\ValidationException;
use Site\Account\Exceptions\InvalidAccountPassword;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Commands\PGPEncryptCommand;
use Site\PGP\Commands\PGPImportPublicKeyCommand;
use Site\PGP\PublicKey;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use Site\DB\SiteDB;


/**
 * Class AccountEntry
 * @table account
 */
class AccountEntry implements IBuildable, IKeyMap, ISerializable
{
	const ID_PREFIX = 'A';
	const SESSION_KEY = 'session_account';
	const FIELD_PASSPHRASE = 'passphrase';

	const JSON_PASSPHRASE_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your decrypted login challenge.",
		" * If you are reading this, that means you successfully decrypted",
		" * your login challenge and authenticated your public key identity.",
		" * \'passphrase\':\'[your challenge passphrase]\'",
		" * To log in: enter the following JSON value as the challenge answer:",
		" */"]
}';
	const KEYRING_NAME = 'accounts.gpg';

	/**
	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	protected $fingerprint;

	/**
	 * @column VARCHAR(64) NOT NULL
	 * @select
	 * @insert
	 * @unique
	 * @search
	 */
	protected $email;

	/**
	 * @column VARCHAR(64) NOT NULL
	 * @select
	 * @insert
	 * @unique
	 * @search
	 */
	protected $name;

	/**
	 * @column TEXT
	 * @insert
	 * @update
	 */
	protected $public_key;

	/**
	 * @column TEXT
	 * @insert
	 * @update
	 */
	protected $challenge;

	/**
	 * @column VARCHAR(64) NOT NULL
	 * @insert
	 * @update
	 */
	protected $answer;

	/**
	 * @column INT
	 * @select
	 * @insert
	 */
	protected $created;

	public function getFingerprint() {
		return $this->fingerprint;
	}

	public function getCreatedTimestamp() {
		return $this->created;
	}

	public function getEmail() {
		return $this->email;
	}

	public function getName() {
		return $this->name;
	}
//

	public function startSession(ISessionRequest $SessionRequest) {
		$SessionRequest->startSession();
		$Session = &$SessionRequest->getSession();
		$Session[AccountEntry::SESSION_KEY] = serialize($this);
		$SessionRequest->endSession();
	}

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
		$Map->map('fingerprint', $this->getFingerprint());
		$Map->map('name', $this->getName());
		$Map->map('email', $this->getEmail());
		$Map->map('created', $this->getCreatedTimestamp());
	}

	public function loadChallenge() {
		return $this->table()
			->select(AccountTable::COLUMN_CHALLENGE)
			->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
			->fetchColumn(0);
	}

	public function loadPublicKey() {
		return $this->table()
			->select(AccountTable::COLUMN_PUBLIC_KEY)
			->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
			->fetchColumn(0);
	}

//	function setPassword(IRequest $Request, $newPassword) {
//		$salt = uniqid('', true);
//		$encryptedPassword = crypt($newPassword, $salt);
//		$this
//			->table()
//			->update(AccountTable::COLUMN_PASSWORD, $encryptedPassword)
//			->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
//			->execute($Request);
//	}

	function assertChallengeAnswer($password, HTMLForm $ThrowForm=null) {
		$encryptedPassword = $this->table()
			->select(AccountTable::COLUMN_ANSWER)
			->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
			->fetchColumn(0);

		if (crypt($password, $encryptedPassword) !== $encryptedPassword) {
			if($ThrowForm)
				throw new ValidationException($ThrowForm, "Invalid Password");
			throw new InvalidAccountPassword("Invalid password");
		}
	}

	/**
	 * @param IRequest $Request
	 * @return string pgp key id
	 * @throws PGPCommandException
	 * @throws \Site\PGP\Exceptions\PGPKeyAlreadyImported
	 */
	public function import(IRequest $Request) {
		$public_key = $this->loadPublicKey();

		$PGPImport = new PGPImportPublicKeyCommand($public_key);
		$PGPImport->setPrimaryKeyRing(static::KEYRING_NAME);
		$PGPImport->execute($Request);
		return $PGPImport->getKeyID();
	}

	public function encrypt(IRequest $Request, $contentsToEncrypt, $armored=false) {
		$additionalRecipients = array();
		$additionalRecipients[] = $this->getFingerprint();
		$PGPEncrypt = new PGPEncryptCommand($additionalRecipients, $contentsToEncrypt);
		$PGPEncrypt->setPrimaryKeyRing(static::KEYRING_NAME);
		if($armored)
			$PGPEncrypt->setArmored();

		try {
			$PGPEncrypt->execute($Request);
			return $PGPEncrypt->getEncryptedString();

		} catch (PGPCommandException $ex) {
			if(strpos($ex->getMessage(), 'not found') !== false) {
				$this->import($Request);
				$PGPEncrypt->execute($Request);
				return $PGPEncrypt->getEncryptedString();
			}

			throw $ex;
		}
	}

	function generateChallenge(IRequest $Request) {
		$passphrase = uniqid("CH");
		$json = json_decode(static::JSON_PASSPHRASE_COMMENTS, true);
		$json[self::FIELD_PASSPHRASE] = $passphrase;
		$json = json_encode($json, JSON_PRETTY_PRINT);

		$encryptedPassword = crypt($passphrase);

		$challenge = $this->encrypt($Request, $json, true);

		$this
			->table()
			->update(AccountTable::COLUMN_CHALLENGE, $challenge)
			->update(AccountTable::COLUMN_ANSWER, $encryptedPassword)
			->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
			->execute($Request);

		return $challenge;
	}


	/**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		$values = (array)$this;
		foreach(array_keys($values) as $key)
			if($values[$key] === null)
				unset($values[$key]);
		return json_encode($values);
	}

	public static function unserialize($serialized) {
		$Inst = new AccountEntry();
		foreach(json_decode($serialized, true) as $name => $value)
			$Inst->$name = $value;
		return $Inst;
	}

	// Static

	static function loadFromSession(ISessionRequest $SessionRequest) {
		if(!$SessionRequest->isStarted())
			$SessionRequest->startSession();
		$Session = $SessionRequest->getSession();

		/** @var AccountEntry $AccountEntry */
		$AccountEntry = unserialize($Session[AccountEntry::SESSION_KEY]);
		if(!$AccountEntry) {
			$SessionRequest->destroySession();
		}
		$SessionRequest->endSession();

		return $AccountEntry;
	}

	static function hasActiveSession(ISessionRequest $SessionRequest) {
		if(!$SessionRequest->isStarted())
			$SessionRequest->startSession();
		$Session = $SessionRequest->getSession();

		$active = !empty($Session[AccountEntry::SESSION_KEY]);
		$SessionRequest->endSession();
		return $active;
	}

	static function create(IRequest $Request, $public_key) {

		$PublicKey = new PublicKey($public_key);

		$fingerprint = $PublicKey->getFingerprint();

		$inserted = self::table()->insert(array(
			AccountTable::COLUMN_FINGERPRINT => $fingerprint,
			AccountTable::COLUMN_NAME => $PublicKey->getUserIDName(),
			AccountTable::COLUMN_EMAIL => $PublicKey->getUserIDEmail(),
			AccountTable::COLUMN_CREATED => time(),
			AccountTable::COLUMN_PUBLIC_KEY => $public_key,
		))
			->execute($Request);

		if(!$inserted)
			throw new \InvalidArgumentException("Could not insert " . __CLASS__);
		$Request->log("New Account Entry Inserted: " . $fingerprint, $Request::VERBOSE);

		$Account = AccountEntry::get($fingerprint);
		$Account->generateChallenge($Request);

		return $Account;
	}

//	static function update($Request, $walletID, $Account, $name=null, $status=null) {
//		$update = array(
//			AccountTable::COLUMN_WALLET => serialize($Account),
//		);
//		$name === null ?: $update[AccountTable::COLUMN_NAME] = $name;
//		$status === null ?: $update[AccountTable::COLUMN_STATUS] = $status;
//		$update = self::table()->update($update)
//			->where(AccountTable::COLUMN_ID, $walletID)
//			->execute($Request);
//		if(!$update)
//			throw new \InvalidArgumentException("Could not update " . __CLASS__);
//	}


	static function delete($Request, $fingerprint) {
		$delete = self::table()->delete(AccountTable::COLUMN_FINGERPRINT, $fingerprint)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

	/**
	 * @param $fingerprint
	 * @return AccountEntry
	 */
	static function get($fingerprint) {
		return self::table()->fetchOne(AccountTable::COLUMN_FINGERPRINT, $fingerprint);
	}

	static function search($search) {
		return self::table()->select()
			->where(AccountTable::COLUMN_FINGERPRINT, $search)
			->orWhere(AccountTable::COLUMN_EMAIL, $search)
			->orWhere(AccountTable::COLUMN_NAME, $search);
	}

	/**
	 * @return AccountTable
	 */
	static function table() {
		return new AccountTable();
	}

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 0
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$Schema = new TableSchema(__CLASS__);
		$DB = new SiteDB();
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\AccountTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}