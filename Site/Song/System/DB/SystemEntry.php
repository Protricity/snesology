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
class SystemEntry implements IBuildable
{
    const ID_PREFIX = 'SS';

    const STATUS_NONE =         0x00;
    const STATUS_APPROVED =     0x01;

    static $StatusOptions = array(
        "Unpublished"       => self::STATUS_NONE,
        "Approved"          => self::STATUS_APPROVED,
    );

    /**
	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	protected $id;

	/**
	 * @column VARCHAR(64) NOT NULL
	 * @select
	 * @insert
	 * @unique
	 * @search
	 */
	protected $name;

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

	// Static
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

