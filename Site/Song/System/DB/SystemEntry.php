<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\System\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use Site\DB\SiteDB;
use Site\Song\System\DefaultGameSystems;

/**
 * Class SystemEntry
 * @table system
 */
class SystemEntry implements IBuildable, IKeyMap
{
    const ID_PREFIX = 'SS';

    const STATUS_NONE =         0x00;
    const STATUS_APPROVED =     0x01;

    static $StatusOptions = array(
        "Unpublished"       => self::STATUS_NONE,
        "Approved"          => self::STATUS_APPROVED,
    );

    public function __construct($name=null) {
        $name === null ?: $this->name = $name;
    }


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
     * @select
     * @insert
     */
    protected $description;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $status;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $created;


	public function getID() {
		return $this->id;
	}

    public function getDescription() {
        return $this->description;
    }

    public function getCreatedTimeStamp() {
        return (int) $this->created;
    }

    public static function getAll() {
        $Query = self::table()
            ->select(SystemTable::COLUMN_NAME);
        $Defaults = new DefaultGameSystems();
        $systemList = array();

        foreach($Defaults->getDefaults() as $system)
            $systemList[$system] = $system;

        while($system = $Query->fetchColumn(0))
            $systemList[$system] = $system;

        return array_values($systemList);
    }

    public function getName() {
		return $this->name;
	}

    public function getStatusFlags() {
        return (int) $this->status;
    }

    public function hasFlags($flags) {
        return $this->getStatusFlags() & $flags;
    }

    public function getStatusList() {
        $statusList = array();
        $statusFlags = $this->getStatusFlags();
        foreach(self::$StatusOptions as $name => $flag) {
            if ($statusFlags & $flag) {
                $statusList[] = substr($name, 7);
            }
        }
        return $statusList;
    }


    public function update(IRequest $Request, $description) {
        $Update = self::table()
            ->update();

        $description === null ?: $Update->update(SystemTable::COLUMN_DESCRIPTION, $description);
        $Update->where(SystemTable::COLUMN_NAME, $this->name);

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
    }

    /**
     * Map data to the key map
     * @param IKeyMapper $Map the map inst to add data to
     * @internal param \CPath\Request\IRequest $Request
     * @internal param \CPath\Request\IRequest $Request
     * @return void
     */
    function mapKeys(IKeyMapper $Map) {
        $Map->map('name', $this->getName());
        $Map->map('description', $this->getDescription());
        $Map->map('created', $this->getCreatedTimeStamp());
        $Map->map('status', implode(', ', $this->getStatusList()));
    }

	// Static

    /**
     * @param null $artist
     * @return PDOSelectBuilder
     */
    static function query($artist=null) {
        $Query = self::table()
            ->select(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_NAME)
            ->select(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_DESCRIPTION)
            ->select(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_CREATED)
            ->select(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_STATUS)

            ->groupBy(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_NAME)

            ->setFetchMode(SystemTable::FETCH_MODE, SystemTable::FETCH_CLASS);

        $artist === null ?: $Query->where(SystemTable::COLUMN_NAME, $artist);

        return $Query;
    }

    /**
     * @param IRequest $Request
     * @param $systemName
     * @return SystemEntry
     */
	static function getOrCreate(IRequest $Request, $systemName) {

        /** @var SystemEntry $System */
        $System = self::table()
            ->select()
            ->where(SystemTable::COLUMN_NAME, $systemName)
            ->fetch();

        if($System)
            return $System->getID();

        $id = strtoupper(uniqid(self::ID_PREFIX));
        $inserted = self::table()->insert(array(
            SystemTable::COLUMN_ID=> $id,
            SystemTable::COLUMN_NAME => $systemName,
            SystemTable::COLUMN_STATUS => self::STATUS_NONE,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("New System Entry Inserted: " . $systemName, $Request::VERBOSE);

        return $id;
	}

	static function table() {
		return new SystemTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SystemTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}

