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
class GenreEntry implements IBuildable
{
    const ID_PREFIX = 'SG';

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

    static function removeFromSong($Request, $id) {
		$delete = self::table()->delete(GenreTable::COLUMN_ID, $id)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

    /**
     * @param IRequest $Request
     * @param $genre
     * @return GenreEntry
     */
	static function getOrCreate(IRequest $Request, $genre) {

        /** @var GenreEntry $Genre */
        $Genre = self::table()
            ->select()
            ->where(GenreTable::COLUMN_NAME, $genre)
            ->fetch();

        if($Genre)
            return $Genre->getID();

        $id = strtoupper(uniqid(self::ID_PREFIX));
        $inserted = self::table()->insert(array(
            GenreTable::COLUMN_ID=> $id,
            GenreTable::COLUMN_NAME => $genre,
            GenreTable::COLUMN_STATUS => self::STATUS_NONE,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("New Genre Entry Inserted: " . $genre, $Request::VERBOSE);

        return $id;
	}

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