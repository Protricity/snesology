<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use Site\DB\SiteDB;

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


//    const Genre_Club = 0x0000001;
//    const Genre_BreakBeat = 0x0000002;
//    const Genre_DubStep = 0x0000004;
//    const Genre_Electro = 0x0000008;
//    const Genre_Garage = 0x0000010;
//    const Genre_Hardcore = 0x0000020;
//    const Genre_Dance = 0x0000040;
//    const Genre_House = 0x0000080;
//    const Genre_Jungle = 0x0000100;
//    const Genre_Techno = 0x0000200;
//    const Genre_Trance = 0x0000400;
//
//    const Genre_HipHop = 0x0001000;
//    const Genre_Country = 0x0002000;
//    const Genre_Comedy = 0x0004000;
//    const Genre_Blues = 0x0008000;
//
//    const Genre_Industrial = 0x0010000;
//    const Genre_Jazz = 0x0020000;
//    const Genre_KPop = 0x0040000;
//    const Genre_Latin = 0x0080000;
//
//    const Genre_Pop = 0x0100000;
//    const Genre_RNB = 0x0200000;
//    const Genre_Rock = 0x0400000;
//    const Genre_World = 0x0800000;
//
//    const Genre_Alternative = 0x1000000;

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
        $genreList = array();
        while($genre = $Query->fetchColumn(0))
            $genreList[] = $genre;
        return $genreList;
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