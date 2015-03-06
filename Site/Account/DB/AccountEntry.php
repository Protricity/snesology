<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Account\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Framework\Data\Serialize\Interfaces\ISerializable;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use CPath\Request\Validation\Exceptions\ValidationException;
use Site\Account\Exceptions\InvalidAccountPassword;
use Site\Account\Guest\GuestAccount;
use Site\Account\Session\AccountSession;
use Site\Account\Session\DB\SessionEntry;
use Site\Account\Session\DB\SessionTable;
use Site\Account\Session\Exceptions\UserSessionNotFound;
use Site\Account\ViewAccount;
use Site\DB\SiteDB;
use Site\Grant\DB\AbstractGrantEntry;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\PGP\Commands\PGPDecryptCommand;
use Site\PGP\Commands\PGPEncryptCommand;
use Site\PGP\Commands\PGPImportPublicKeyCommand;
use Site\PGP\Exceptions\PGPKeyAlreadyImported;
use Site\PGP\PublicKey;


/**
 * Class AccountEntry
 * @table account
 */
class AccountEntry extends AbstractGrantEntry implements IBuildable, IKeyMap, ISerializable, IRenderHTML
{
    const ID_PREFIX = 'A';
//    const SESSION_KEY = 'session_account';

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

    public function __construct($fingerprint=null, $public_key=null) {
        $fingerprint === null ?: $this->fingerprint = $fingerprint;
        $public_key === null ?: $this->public_key = $public_key;
    }

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

    /**
     * @column VARCHAR(64)
     * @select
     * @search
     */
    protected $invite_fingerprint;


    public function getFingerprint() {
        return $this->fingerprint;
    }

    /**
     * @return mixed
     */
    public function getInviteFingerprint() {
        return $this->invite_fingerprint;
    }


    public function getCreatedTimestamp() {
        return $this->created;
    }

    public function getEmail($protect=true) {
        return $protect ? '*****' . strstr($this->email, '@') : $this->email;
    }

    public function getName() {
        return $this->name;
    }
//

    /**
     * Start a new session
     * @param ISessionRequest $SessionRequest
     * @return SessionEntry
     */
    public function startSession(ISessionRequest $SessionRequest) {
        return SessionEntry::create($SessionRequest, $this->getFingerprint());
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
        $Map->map('inviter', $this->getInviteFingerprint());
        $Map->map('name', $this->getName());
        $Map->map('email', $this->getEmail());
        $Map->map('created', $this->getCreatedTimestamp());
        $Map->map('public-key', $this->public_key);
    }

    public function loadChallenge(IRequest $Request) {
        $challenge = $this->table()
            ->select(AccountTable::COLUMN_CHALLENGE)
            ->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
            ->fetchColumn(0);

        if(!$challenge)
            try {
                $challenge = $this->generateChallenge($Request, array($this->getFingerprint()));
            } catch (PGPCommandException $ex) {
                if (strpos($ex->getMessage(), 'not found') !== false) {
                    $this->import($Request);
                    $challenge = $this->generateChallenge($Request, array($this->getFingerprint()));
                } else {
                    throw $ex;
                }
            }

        return $challenge;
    }

    public function loadChallengeAnswer(IRequest $Request) {
        $answer = $this->table()
            ->select(AccountTable::COLUMN_ANSWER)
            ->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
            ->fetchColumn(0);

        return $answer;
    }

    public function loadPublicKey() {
        if($this->public_key)
            return $this->public_key;
        return $this->table()
            ->select(AccountTable::COLUMN_PUBLIC_KEY)
            ->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
            ->fetchColumn(0);
    }

    /**
     * @param IRequest $Request
     * @return string pgp key id
     * @throws PGPCommandException
     * @throws \Site\PGP\Exceptions\PGPKeyAlreadyImported
     */
    public function import(IRequest $Request) {
        $public_key = $this->loadPublicKey($Request);

        $PGPImport = new PGPImportPublicKeyCommand($public_key);
        $PGPImport->setPrimaryKeyRing(static::KEYRING_NAME);
        $PGPImport->execute($Request);
        return $PGPImport->getKeyID();
    }

    public function encrypt(IRequest $Request, $contentsToEncrypt, Array $recipients = array(), $armored = false) {
        $PGPEncrypt = new PGPEncryptCommand($recipients, $contentsToEncrypt);
        $PGPEncrypt->addKeyRing(static::KEYRING_NAME);
        if ($armored)
            $PGPEncrypt->setArmored();

        try {
            $PGPEncrypt->execute($Request);
            return $PGPEncrypt->getEncryptedString();

        } catch (PGPCommandException $ex) {
            if (strpos($ex->getMessage(), 'not found') !== false) {
                $this->import($Request);
                $PGPEncrypt->execute($Request);
                return $PGPEncrypt->getEncryptedString();
            }

            throw $ex;
        }
    }

    public function verify(IRequest $Request, $invite) {
        $PGPDecrypt = new PGPDecryptCommand($invite);
        $PGPDecrypt->addKeyRing(static::KEYRING_NAME);

        try {
            $PGPDecrypt->execute($Request);
            return $PGPDecrypt->getDecryptedString();

        } catch (PGPCommandException $ex) {
            if (strpos($ex->getMessage(), 'not found') !== false) {
                $this->import($Request);
                $PGPDecrypt->execute($Request);
                return $PGPDecrypt->getDecryptedString();
            }

            throw $ex;
        }
    }

    protected function updateChallenge(IRequest $Request, $newChallenge, $newAnswer) {
        $this
            ->table()
            ->update(AccountTable::COLUMN_CHALLENGE, $newChallenge)
            ->update(AccountTable::COLUMN_ANSWER, $newAnswer)
            ->where(AccountTable::COLUMN_FINGERPRINT, $this->fingerprint)
            ->execute($Request);
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        echo "<a href='", $Request->getDomainPath() . ltrim(ViewAccount::getRequestURL($this->getFingerprint()), '/'), "'>", $this->getName(), "</a>";
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

    // Static

    /** @var array AccountEntry */
    static private $SessionCache = array();

    /**
     * @param ISessionRequest|IRequest $SessionRequest
     * @return bool|mixed|AccountEntry
     * @throws \Exception
     */
    static function loadFromSession(ISessionRequest $SessionRequest) {
        $sessionID = $SessionRequest->getSessionID();
        if(!$sessionID) {
            $SessionRequest->startSession();
            $sessionID = $SessionRequest->getSessionID();
        }

        if(isset(self::$SessionCache[$sessionID]))
            return self::$SessionCache[$sessionID];

        $AccountEntry = self::query()
            ->leftJoin(SessionTable::TABLE_NAME, AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_FINGERPRINT, SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_FINGERPRINT)
            ->where(SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_ID, $sessionID)
            ->fetch();

        if(!$AccountEntry) {
            $AccountEntry = AccountEntry::query()
                ->where(AccountTable::COLUMN_FINGERPRINT, GuestAccount::PGP_FINGERPRINT)
                ->fetch();

            if(!$AccountEntry) {
                $AccountEntry = AccountEntry::create($SessionRequest, GuestAccount::PGP_PUBLIC_KEY);
            }

            $SessionEntry = SessionEntry::table()->fetch(SessionTable::COLUMN_ID, $sessionID);
            if($SessionEntry) {
                $SessionEntry->update($SessionRequest, GuestAccount::PGP_FINGERPRINT);
            } else {
                $SessionEntry = SessionEntry::create($SessionRequest, GuestAccount::PGP_FINGERPRINT);
            }
        }
        self::$SessionCache[$sessionID] = $AccountEntry;

        return $AccountEntry;
    }

    public static function unserialize($serialized) {
        $Inst = new AccountEntry();
        foreach(json_decode($serialized, true) as $name => $value)
            $Inst->$name = $value;
        return $Inst;
    }

    static function create(IRequest $Request, $publicKeyString, $inviteEmail=null, $inviteFingerprint=null) {
        $PGPImport = new PGPImportPublicKeyCommand($publicKeyString);
        $PGPImport->setPrimaryKeyRing(AccountEntry::KEYRING_NAME);
        try {
            $PGPImport->execute($Request);
        } catch (PGPKeyAlreadyImported $ex) {}
//        $keyID = $PGPImport->getKeyID();

        $PublicKey = new PublicKey($publicKeyString);
        if($inviteEmail && $inviteEmail !== $PublicKey->getUserIDEmail())
            throw new \Exception("Only invitee's email may be used");

        $fingerprint = $PublicKey->getFingerprint();

        $inserted = self::table()->insert(array(
            AccountTable::COLUMN_FINGERPRINT => $fingerprint,
            AccountTable::COLUMN_INVITE_FINGERPRINT => $inviteFingerprint,
            AccountTable::COLUMN_NAME => $PublicKey->getUserIDName(),
            AccountTable::COLUMN_EMAIL => $PublicKey->getUserIDEmail(),
            AccountTable::COLUMN_CREATED => time(),
            AccountTable::COLUMN_PUBLIC_KEY => $publicKeyString,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("New Account Entry Inserted: " . $fingerprint, $Request::VERBOSE);

        $Account = AccountEntry::get($fingerprint);
//        $Account->import($Request);
        $Account->generateChallenge($Request, array($Account->getFingerprint()));

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
     * @param string $compare
     * @return AccountEntry
     */
    static function fetch($fingerprint, $compare = '=?') {
        return self::query()
            ->where(AccountTable::COLUMN_FINGERPRINT, $fingerprint, $compare)
            ->fetch();
    }

    /**
     * @param $fingerprint
     * @return AccountEntry
     */
    static function get($fingerprint) {
        return self::query()
            ->where(AccountTable::COLUMN_FINGERPRINT, $fingerprint)
            ->fetchOne();
    }

    static function search($search) {
        return self::query()
            ->where(AccountTable::COLUMN_FINGERPRINT, $search)
            ->orWhere(AccountTable::COLUMN_EMAIL, $search)
            ->orWhere(AccountTable::COLUMN_NAME, $search);
    }

    /**
     * @param bool $withPublicKey
     * @return PDOSelectBuilder
     */
    static function query($withPublicKey=false) {
        $Select =self::table()
            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_FINGERPRINT)
            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_NAME)
            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_EMAIL)
            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_CREATED)
            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_INVITE_FINGERPRINT)

            ->setFetchMode(AccountTable::FETCH_MODE, AccountTable::FETCH_CLASS);
        if($withPublicKey)
            $Select->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_PUBLIC_KEY);
        return $Select;
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

