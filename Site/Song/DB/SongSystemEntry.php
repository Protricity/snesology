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
 * Class SongSystemEntry
 * @table song_system
 */
class SongSystemEntry implements IBuildable
{
    /**
	 * @column VARCHAR(64) NOT NULL
     * @unique --name unique_song_system
	 */
	protected $song_id;

	/**
	 * @column VARCHAR(64) NOT NULL
	 * @unique --name unique_song_system
	 */
	protected $system_id;


	public function getSongID() {
		return $this->song_id;
	}

	public function getSystemID() {
		return $this->system_id;
	}

	// Static

    static function removeFromSong($Request, $songID, $system) {
        $systemID = SystemEntry::getOrCreate($Request, $system);

        $delete = self::table()
            ->delete()
            ->where(SongSystemTable::COLUMN_SONG_ID, $songID)
            ->where(SongSystemTable::COLUMN_SYSTEM_ID, $systemID)
            ->execute($Request);
        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSong(IRequest $Request, $songID, $system) {
        $systemID = SystemEntry::getOrCreate($Request, $system);

        $inserted = self::table()->insert(array(
            SongSystemTable::COLUMN_SYSTEM_ID => $systemID,
            SongSystemTable::COLUMN_SONG_ID => $songID,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("System added to song: " . $system, $Request::VERBOSE);
    }

    static function table() {
        return new SongSystemTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SongSystemTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}