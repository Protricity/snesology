<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Relay\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use Site\Account\DB\AccountTable;
use Site\DB\SiteDB;


/**
 * Class RelayLogEntry
 * @table relay_log
 */
class RelayLogEntry implements IBuildable, IKeyMap
{
    const JOIN_COLUMN_ACCOUNT_NAME = 'account_name';
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
        $Map->map('created', $this->getCreatedTimestamp());
    }

    // Static

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(RelayLogTable::TABLE_NAME . '.' . RelayLogTable::COLUMN_PATH)
            ->select(RelayLogTable::TABLE_NAME . '.' . RelayLogTable::COLUMN_ACCOUNT_FINGERPRINT)
            ->select(RelayLogTable::TABLE_NAME . '.' . RelayLogTable::COLUMN_LOG)
            ->select(RelayLogTable::TABLE_NAME . '.' . RelayLogTable::COLUMN_CREATED)

            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_NAME, self::JOIN_COLUMN_ACCOUNT_NAME)
            ->leftJoin(AccountTable::TABLE_NAME, AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_FINGERPRINT, RelayLogTable::TABLE_NAME . '.' . RelayLogTable::COLUMN_ACCOUNT_FINGERPRINT)

            ->setFetchMode(RelayLogTable::FETCH_MODE, RelayLogTable::FETCH_CLASS);
    }


    static function create(IRequest $Request, $path, $accountFingerprint, $log) {
		$inserted = self::table()->insert(array(
            RelayLogTable::COLUMN_PATH => $path,
            RelayLogTable::COLUMN_ACCOUNT_FINGERPRINT => $accountFingerprint,
            RelayLogTable::COLUMN_LOG => $log,
            RelayLogTable::COLUMN_CREATED => time(),
		))
			->execute($Request);

		if(!$inserted)
			throw new \InvalidArgumentException("Could not insert " . __CLASS__);

		$Request->log("New Relay Entry Inserted", $Request::VERBOSE);
	}

	/**
	 * @return RelayLogTable
	 */
	static function table() {
		return new RelayLogTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\RelayLogTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}