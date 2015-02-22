<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\DB;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Framework\Data\Serialize\Interfaces\ISerializable;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use Site\DB\SiteDB;

/**
 * Class SongEntry
 * @table song
 */
class SongEntry implements IBuildable, IKeyMap, ISerializable
{
	const ID_PREFIX = 'S';

    const STATUS_NONE =         0x00;
    const STATUS_PUBLISHED =    0x01;

    const JOIN_COLUMN_SYSTEMS = 'systems';
    const JOIN_COLUMN_GENRES = 'genres';

    static $StatusOptions = array(
        "Unpublished"       => self::STATUS_NONE,
        "Published"         => self::STATUS_PUBLISHED,
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
	protected $title;

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

    protected $systems;
    protected $genres;

	public function getID() {
		return $this->id;
	}

	public function getCreatedTimestamp() {
		return $this->created;
	}

    public function getTitle() {
        return $this->title;
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

    public function getGenreList() {
        return explode(', ', $this->genres);
    }

    public function getSystemList() {
        return explode(', ', $this->systems);
    }

    public function updateTitle(IRequest $Request, $title) {
        $update = self::table()
            ->update(SongTable::COLUMN_TITLE, $title)
            ->where(SongTable::COLUMN_ID, $this->id)
            ->execute($Request);
        if(!$update)
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
        $Map->map('fingerprint', $this->getID());
        $Map->map('title', $this->getTitle());
        $Map->map('created', $this->getCreatedTimestamp());
        $Map->map('status', implode(', ', $this->getStatusList()));
        $Map->map('genres', $this->genres);
        $Map->map('systems', $this->systems);
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
        $Inst = new SongEntry();
        foreach(json_decode($serialized, true) as $name => $value)
            $Inst->$name = $value;
        return $Inst;
    }

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_TITLE)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_CREATED)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_STATUS)

            ->select(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_NAME, self::JOIN_COLUMN_GENRES, 'GROUP_CONCAT(%s SEPARATOR ", ")')
            ->join(SongGenreTable::TABLE_NAME, SongGenreTable::TABLE_NAME . '.' . SongGenreTable::COLUMN_SONG_ID, SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)
            ->join(GenreTable::TABLE_NAME, GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_ID, SongGenreTable::COLUMN_GENRE_ID)

            ->select(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_NAME, self::JOIN_COLUMN_SYSTEMS, 'GROUP_CONCAT(%s SEPARATOR ", ")')
            ->join(SongSystemTable::TABLE_NAME, SongSystemTable::TABLE_NAME . '.' . SongSystemTable::COLUMN_SONG_ID, SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)
            ->join(SystemTable::TABLE_NAME, SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_ID, SongSystemTable::COLUMN_SYSTEM_ID)

            ->groupBy(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)

            ->setFetchMode(SongTable::FETCH_MODE, SongTable::FETCH_CLASS);
    }

    static function create(IRequest $Request, $title) {
        $id = strtoupper(uniqid(self::ID_PREFIX));

        $inserted = self::table()->insert(array(
            SongTable::COLUMN_ID => $id,
            SongTable::COLUMN_TITLE => $title,
            SongTable::COLUMN_STATUS => self::STATUS_NONE,
            SongTable::COLUMN_CREATED => time(),
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("New Song Entry Inserted: " . $title, $Request::VERBOSE);

        $Song = SongEntry::get($id);

        return $Song;
    }

	static function delete($Request, $id) {
		$delete = self::table()->delete(SongTable::COLUMN_ID, $id)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

	/**
	 * @param $id
	 * @return SongEntry
	 */
	static function get($id) {
		return self::table()->fetchOne(SongTable::COLUMN_ID, $id);
	}

	static function table() {
		return new SongTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SongTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}