<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Request\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use Site\Account\DB\AccountEntry;
use Site\Account\DB\AccountTable;
use Site\DB\SiteDB;


/**
 * Class RequestEntry
 * @table request
 */
class RequestEntry implements IBuildable, IKeyMap
{

    const ID_PREFIX = 'R';

    const STATUS_PENDING =              0x000001;
    const STATUS_COMMITTED =            0x000002;
    const JOIN_COLUMN_ACCOUNT_NAME = 'account_name';


    static $StatusOptions = array(
        "Pending" =>              self::STATUS_PENDING,
        "Committed" =>              self::STATUS_COMMITTED,
    );

    /**
     * @column VARCHAR(64) PRIMARY KEY
     * @select
     * @search
     */
    protected $id;

    /**
	 * @column VARCHAR(128)
     * @insert
     * @update
     * @select
	 * @search
	 */
	protected $path;

    /**
     * @column VARCHAR(64)
     * @insert
     * @update
     * @select
     * @search
     */
    protected $account_fingerprint;

    /**
     * @column TEXT
     * @select
     * @update
     * @insert
     */
    protected $request;

    /**
     * @column TEXT
     * @select
     * @update
     * @insert
     * @search
     */
    protected $log;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $created;

    protected $account_name;

    public function getAccountFingerprint() {
        return $this->account_fingerprint;
    }

    public function getAccountName() {
        return $this->account_name;
    }

    public function getLog() {
        return $this->log;
    }

    public function getPath() {
        return $this->path;
    }

    public function getCreatedTimestamp() {
        return $this->created;
    }

    public function getRequestData() {
        return unserialize($this->request);
    }
    
	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
        $Map->map('path', $this->getPath());
        $Map->map('account', $this->getAccountFingerprint());
        $Map->map('log', $this->getLog());
        $Map->map('request', $this->getRequestData());
        $Map->map('created', $this->getCreatedTimestamp());
    }

    // Static

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(RequestTable::TABLE_NAME . '.' . RequestTable::COLUMN_PATH)
            ->select(RequestTable::TABLE_NAME . '.' . RequestTable::COLUMN_ACCOUNT_FINGERPRINT)
            ->select(RequestTable::TABLE_NAME . '.' . RequestTable::COLUMN_LOG)
            ->select(RequestTable::TABLE_NAME . '.' . RequestTable::COLUMN_REQUEST)
            ->select(RequestTable::TABLE_NAME . '.' . RequestTable::COLUMN_CREATED)

            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_NAME, self::JOIN_COLUMN_ACCOUNT_NAME)
            ->leftJoin(AccountTable::TABLE_NAME, AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_FINGERPRINT, RequestTable::TABLE_NAME . '.' . RequestTable::COLUMN_ACCOUNT_FINGERPRINT)

            ->setFetchMode(RequestTable::FETCH_MODE, RequestTable::FETCH_CLASS);
    }

    static function createFromRequest(IRequest $Request, AccountEntry $Account, $log=null) {
        $path = $Request->getPath();
        $accountFingerprint = $Account->getFingerprint();
        $requestData = array();
        foreach($Request as $key => $value)
            $requestData[$key] = $value;
        self::create($Request, $path, $accountFingerprint, $requestData, $log);
    }

    static function create(IRequest $Request, $path, $accountFingerprint, $requestData, $log=null) {
        $id = strtoupper(uniqid(self::ID_PREFIX));

        $inserted = self::table()->insert(array(
            RequestTable::COLUMN_ID => $id,
            RequestTable::COLUMN_PATH => $path,
            RequestTable::COLUMN_REQUEST => serialize($requestData),
            RequestTable::COLUMN_ACCOUNT_FINGERPRINT => $accountFingerprint,
            RequestTable::COLUMN_LOG => $log,
            RequestTable::COLUMN_CREATED => time(),
		))
			->execute($Request);

		if(!$inserted)
			throw new \InvalidArgumentException("Could not insert " . __CLASS__);

		$Request->log("New Pending Request Inserted: " . $path, $Request::VERBOSE);
	}

	/**
	 * @return RequestTable
	 */
	static function table() {
		return new RequestTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\RequestTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}