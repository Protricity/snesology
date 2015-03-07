<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Account\Session\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use CPath\Request\Session\ISessionRequest;
use Site\Account\DB\AccountEntry;
use Site\DB\SiteDB;

/**
 * Class SessionEntry
 * @table session
 */
class SessionEntry implements IBuildable, IKeyMap
{
    const ID_PREFIX = '$';
    const SESSION_KEY = 'fp';

    public function __construct($sessionID=null, $accountFingerprint=null) {
        $accountFingerprint === null ?: $this->fingerprint = $accountFingerprint;
        $sessionID === null ?: $this->id = $sessionID;
    }

    /**
     * @column VARCHAR(64) PRIMARY KEY
     * @select
     * @search
     */
    protected $id;

    /**
     * @column VARCHAR(64)
     * @select
     * @insert
     * @search
     */
    protected $fingerprint;

    /**
     * @column TEXT
     * @insert
     * @update
     */
    protected $fields;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $created;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $status;

    public function getID() {
        return $this->id;
    }

    public function getFingerprint() {
        return $this->fingerprint;
    }

    public function getCreatedTimestamp() {
        return $this->created;
    }

    public function getFields() {
        return $this->fields ? json_decode($this->fields) : array();
    }

    /**
     * Map data to the key map
     * @param IKeyMapper $Map the map inst to add data to
     * @internal param \CPath\Request\IRequest $Request
     * @internal param \CPath\Request\IRequest $Request
     * @return void
     */
    function mapKeys(IKeyMapper $Map) {
        $Map->map('session-id', '...' . substr($this->getID(), -8));
        $Map->map('fingerprint', $this->getFingerprint());
        $Map->map('created', $this->getCreatedTimestamp());
        foreach($this->getFields() as $name => $value)
            $Map->map(str_replace('_', '-', strtolower($name)), $value);
    }

    public function update(IRequest $Request, $accountFingerprint) {
        $Update = self::table()
            ->update();

        $accountFingerprint === null ?: $Update->update(SessionTable::COLUMN_FINGERPRINT, $accountFingerprint);
        $Update->where(SessionTable::COLUMN_ID, $this->id);

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
    }

    // Static

    /** @var SessionEntry[] */
    static private $SessionCache = array();
//
//    static function hasActiveSession(ISessionRequest $SessionRequest) {
//        $sessionID = $SessionRequest->getSessionID();
//        if(!$sessionID)
//            return false;
//
//        if(isset(self::$SessionCache[$sessionID]))
//            return true;
//
//
//        $started = $SessionRequest->isStarted();
//        if(!$started)
//            $SessionRequest->startSession();
//        $Session = $SessionRequest->getSession();
//
//        $active = !empty($Session[SessionEntry::SESSION_KEY]);
//        if(!$started)
//            $SessionRequest->endSession();
//        return $active;
//    }

    static function loadFromSession(ISessionRequest $SessionRequest) {
        $sessionID = $SessionRequest->getSessionID();
        if(!$sessionID)
            throw new \InvalidArgumentException("No Session Cookie");

        if(isset(self::$SessionCache[$sessionID]))
            return self::$SessionCache[$sessionID];

        $SessionEntry = self::table()->fetch(SessionTable::COLUMN_ID, $sessionID);

        if(!$SessionEntry) {
            $SessionEntry = self::create($SessionRequest, null);
        }

        self::$SessionCache[$sessionID] = $SessionEntry;

        return $SessionEntry;
    }

    static function startNewSession(ISessionRequest $SessionRequest, $accountFingerprint) {
        $SessionRequest->startSession();
        $Session = &$SessionRequest->getSession();
        $Session[SessionEntry::SESSION_KEY] = $accountFingerprint;
        $SessionRequest->endSession();
    }

    /**
     * @param ISessionRequest|IRequest $Request
     * @param $accountFingerprint
     * @return SessionEntry
     * @throws \CPath\Data\Schema\PDO\PDODuplicateRowException
     */
    static function create(ISessionRequest $Request, $accountFingerprint) {
        $sessionID = $Request->getSessionID();
        if(!$sessionID) {
            $Request->destroySession();
            $Request->startSession();
            $sessionID = $Request->getSessionID();
        }

        $fields = array();

        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $fields['ip'] = $_SERVER["HTTP_X_FORWARDED_FOR"];
            $fields['proxy'] = $_SERVER["REMOTE_ADDR"];
            $fields['host'] = @gethostbyaddr($_SERVER["HTTP_X_FORWARDED_FOR"]);

        } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $fields['ip'] = $_SERVER["REMOTE_ADDR"];
            $fields['host'] = @gethostbyaddr($_SERVER["REMOTE_ADDR"]);

        }

        $inserted = self::table()->insert(array(
            SessionTable::COLUMN_ID => $sessionID,
            SessionTable::COLUMN_FINGERPRINT => $accountFingerprint,
            SessionTable::COLUMN_FIELDS => json_encode($fields),
            SessionTable::COLUMN_CREATED => time(),
        ))
            ->onDuplicateKeyUpdate(SessionTable::COLUMN_FINGERPRINT, $accountFingerprint)
            ->onDuplicateKeyUpdate(SessionTable::COLUMN_FIELDS, json_encode($fields))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("New Session Entry Inserted: " . $sessionID, $Request::VERBOSE);

        $SessionEntry = SessionEntry::get($sessionID);
        self::$SessionCache[$sessionID] = $SessionEntry;
        return $SessionEntry;
    }

    static function delete($Request, $sessionID) {
        $delete = self::table()->delete(SessionTable::COLUMN_ID, $sessionID)
            ->execute($Request);
        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function deleteAll($Request, $accountFingerprint) {
        $delete = self::table()->delete(SessionTable::COLUMN_FINGERPRINT, $accountFingerprint)
            ->execute($Request);
        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    /**
     * @param $session_id
     * @throws \CPath\Data\Schema\PDO\PDONotFoundException
     * @throws \CPath\Data\Schema\PDO\PDOQueryException
     * @return SessionEntry
     */
    static function get($session_id) {
        return self::query()
            ->where(SessionTable::COLUMN_ID, $session_id)
            ->fetch();
    }

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        $Select = self::table()
            ->select(SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_ID)
            ->select(SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_FINGERPRINT)
            ->select(SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_CREATED)
            ->select(SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_FIELDS)
            ->select(SessionTable::TABLE_NAME . '.' . SessionTable::COLUMN_STATUS)

            ->setFetchMode(SessionTable::FETCH_MODE, SessionTable::FETCH_CLASS);
        return $Select;
    }

    /**
     * @return SessionTable
     */
    static function table() {
        return new SessionTable();
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
        $ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SessionTable', __CLASS__);
        $Schema->writeSchema($ClassWriter);
        $DBWriter = new PDOTableWriter($DB);
        $Schema->writeSchema($DBWriter);
    }
}
