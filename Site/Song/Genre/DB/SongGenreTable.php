<?php
namespace Site\Song\Genre\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Genre\DB\SongGenreEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class SongGenreTable
 * @table song_genre
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SongGenreEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SongGenreEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SongGenreEntry[]
 */
class SongGenreTable extends AbstractBase {
	const TABLE_NAME = 'song_genre';
	const FETCH_CLASS = 'Site\\Song\\Genre\\DB\\SongGenreEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @unique --name unique_song_system
	 */
	const COLUMN_SONG_ID = 'song_id';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @unique --name unique_song_system
	 */
	const COLUMN_GENRE_ID = 'genre_id';
	/**

	 * @index UNIQUE
	 * @columns song_id, genre_id
	 */
	const UNIQUE_SONG_SYSTEM = 'unique_song_system';

	function getSchema() { return new TableSchema('Site\\Song\\Genre\\DB\\SongGenreEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}