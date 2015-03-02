<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\Artist\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Framework\Data\Serialize\Interfaces\ISerializable;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Request\IRequest;
use CPath\Request\Validation\Exceptions\ValidationException;
use Site\DB\SiteDB;

/**
 * Class ArtistEntry
 * @table artist
 */
class ArtistEntry implements IBuildable, IKeyMap, ISerializable
{
	const ID_PREFIX = 'SA';

//    const STATUS_NONE =                 0x000000;
    const STATUS_PUBLISHED =            0x000001;

    static $StatusOptions = array(
//        "Unpublished" =>            self::STATUS_NONE,
        "Published" =>              self::STATUS_PUBLISHED,
    );

    public function __construct($name=null) {
        $name === null ?: $this->name = $name;
    }

    /**
	 * @column VARCHAR(64) PRIMARY KEY
     * @unique
	 * @select
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
     * @column VARCHAR(128)
     * @select
     */
    protected $url;

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

	public function getName() {
		return $this->name;
	}

    public function getUrl() {
        return $this->url;
    }

	public function getCreatedTimestamp() {
		return $this->created;
	}

    public function getDescription() {
        return $this->description;
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
        return $statusList ?: array("Unpublished");
    }

    public function update(IRequest $Request, $name=null, $description=null, $url=null) {
        if($this->hasFlags(self::STATUS_PUBLISHED))
            $name = null;

        $Update = self::table()
            ->update();

        $name === null ?: $Update->update(ArtistTable::COLUMN_NAME, $name);
        $description === null ?: $Update->update(ArtistTable::COLUMN_DESCRIPTION, $description);
        $url === null ?: $Update->update(ArtistTable::COLUMN_URL, $url);
        $Update->where(ArtistTable::COLUMN_NAME, $this->name);

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);

        $this->name = $name;
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
        $Map->map('created', $this->getCreatedTimestamp());
        $Map->map('description', $this->getDescription());
        $Map->map('url', $this->getUrl());
        $Map->map('status', implode(', ', $this->getStatusList()));
	}

    public function publish(IRequest $Request, HTMLForm $Form=null) {
        if($this->hasFlags(ArtistEntry::STATUS_PUBLISHED))
            throw new ValidationException($Form, "Artist is already published");

        $status = $this->status | self::STATUS_PUBLISHED;
        self::table()
            ->update(ArtistTable::COLUMN_STATUS, $status)
            ->where(ArtistTable::COLUMN_NAME, $this->name)
            ->execute($Request);
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

    public static function unserialize($serialized) {
        $Inst = new ArtistEntry();
        foreach(json_decode($serialized, true) as $name => $value)
            $Inst->$name = $value;
        return $Inst;
    }

    /**
     * @param null $artist
     * @return PDOSelectBuilder
     */
    static function query($artist=null) {
        $Query = self::table()
            ->select(ArtistTable::TABLE_NAME . '.' . ArtistTable::COLUMN_NAME)
            ->select(ArtistTable::TABLE_NAME . '.' . ArtistTable::COLUMN_DESCRIPTION)
            ->select(ArtistTable::TABLE_NAME . '.' . ArtistTable::COLUMN_CREATED)
            ->select(ArtistTable::TABLE_NAME . '.' . ArtistTable::COLUMN_STATUS)

            ->groupBy(ArtistTable::TABLE_NAME . '.' . ArtistTable::COLUMN_NAME)

            ->setFetchMode(ArtistTable::FETCH_MODE, ArtistTable::FETCH_CLASS);

        $artist === null ?: $Query->where(ArtistTable::COLUMN_NAME, $artist);

        return $Query;
    }

    static function create(IRequest $Request, $name, $description) {
//        $id = strtoupper(uniqid(self::ID_PREFIX));
        $inserted = self::table()->insert(array(
            ArtistTable::COLUMN_NAME => $name,
            ArtistTable::COLUMN_DESCRIPTION => $description,
            ArtistTable::COLUMN_STATUS => 0,
            ArtistTable::COLUMN_CREATED => time(),
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);

        $Request->log("New Artist Entry Inserted: " . $name, $Request::VERBOSE);

        $Artist = ArtistEntry::get($name);

        return $Artist;
    }

	static function delete($Request, $name) {
		$delete = self::table()
            ->delete(ArtistTable::COLUMN_NAME, $name)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

    /**
     * @param $name
     * @return ArtistEntry
     */
    static function get($name) {
        return self::query()
            ->where(ArtistTable::TABLE_NAME . '.' . ArtistTable::COLUMN_NAME, $name)
            ->fetchOne();
    }

	static function table() {
		return new ArtistTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\ArtistTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}


}