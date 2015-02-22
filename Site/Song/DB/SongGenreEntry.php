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
 * Class SongGenreEntry
 * @table song_genre
 */
class SongGenreEntry implements IBuildable
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
	protected $genre_id;

	public function getSongID() {
		return $this->song_id;
	}

	public function getGenreID() {
		return $this->genre_id;
	}

	// Static

    static function removeFromSong($Request, $songID, $genre) {
        $genreID = GenreEntry::getOrCreate($Request, $genre);

        $delete = self::table()
            ->delete()
            ->where(SongSystemTable::COLUMN_SONG_ID, $songID)
            ->where(SongSystemTable::COLUMN_SYSTEM_ID, $genreID)
            ->execute($Request);
        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSong(IRequest $Request, $songID, $genre) {
        $genreID = GenreEntry::getOrCreate($Request, $genre);

        $inserted = self::table()->insert(array(
            SongGenreTable::COLUMN_GENRE_ID => $genreID,
            SongGenreTable::COLUMN_SONG_ID => $songID,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Genre added to song: " . $genre, $Request::VERBOSE);
    }

    static function table() {
        return new SongGenreTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SongGenreTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}