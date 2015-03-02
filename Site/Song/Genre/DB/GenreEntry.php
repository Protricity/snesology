<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\Genre\DB;
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
use Site\Song\Genre\DefaultGenres;

/**
 * Class GenreEntry
 * @table genre
 */
class GenreEntry implements IBuildable, IKeyMap
{
    const ID_PREFIX = 'SG';

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

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getStatusFlags() {
        return (int) $this->status;
    }

    public function getCreatedTimeStamp() {
        return (int) $this->created;
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

        $description === null ?: $Update->update(GenreTable::COLUMN_DESCRIPTION, $description);
        $Update->where(GenreTable::COLUMN_NAME, $this->name);

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
        $Map->map('created', $this->created);
        $Map->map('status', implode(', ', $this->getStatusList()));
    }

	// Static

    /**
     * @param null $artist
     * @return PDOSelectBuilder
     */
    static function query($artist=null) {
        $Query = self::table()
            ->select(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_NAME)
            ->select(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_DESCRIPTION)
            ->select(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_CREATED)
            ->select(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_STATUS)

            ->groupBy(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_NAME)

            ->setFetchMode(GenreTable::FETCH_MODE, GenreTable::FETCH_CLASS);

        $artist === null ?: $Query->where(GenreTable::COLUMN_NAME, $artist);

        return $Query;
    }

    
    public static function getAll() {
        $Query = self::table()
            ->select(GenreTable::COLUMN_NAME);
        $Defaults = new DefaultGenres();
        $genreList = array();

        foreach($Defaults->getDefaults() as $genre)
            $genreList[$genre] = $genre;

        while($genre = $Query->fetchColumn(0))
            $genreList[$genre] = $genre;

        sort($genreList);
        return array_values($genreList);
    }

//    static function removeFromSong($Request, $id) {
//		$delete = self::table()->delete(GenreTable::COLUMN_ID, $id)
//			->execute($Request);
//		if(!$delete)
//			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
//	}
//
//    /**
//     * @param IRequest $Request
//     * @param $genre
//     * @return GenreEntry
//     */
//	static function getOrCreate(IRequest $Request, $genre) {
//
//        /** @var GenreEntry $Genre */
//        $Genre = self::table()
//            ->select()
//            ->where(GenreTable::COLUMN_NAME, $genre)
//            ->fetch();
//
//        if($Genre)
//            return $Genre->getID();
//
//        $id = strtoupper(uniqid(self::ID_PREFIX));
//        $inserted = self::table()->insert(array(
//            GenreTable::COLUMN_ID=> $id,
//            GenreTable::COLUMN_NAME => $genre,
//            GenreTable::COLUMN_STATUS => self::STATUS_NONE,
//        ))
//            ->execute($Request);
//
//        if(!$inserted)
//            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
//        $Request->log("New Genre Entry Inserted: " . $genre, $Request::VERBOSE);
//
//        return $id;
//	}

	static function table() {
		return new GenreTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\GenreTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}