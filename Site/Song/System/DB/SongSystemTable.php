<?php
namespace Site\Song\System\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\System\DB\SongSystemEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class SongSystemTable
 * @table song_system
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SongSystemEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SongSystemEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SongSystemEntry[]
 */
class SongSystemTable extends AbstractBase {
	const TABLE_NAME = 'song_system';
	const FETCH_CLASS = 'Site\\Song\\System\\DB\\SongSystemEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @unique --name unique_song_system
	 */
	const COLUMN_SONG_ID = 'song_id';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @unique --name unique_song_system
	 */
	const COLUMN_SYSTEM_ID = 'system_id';
	/**

	 * @index UNIQUE
	 * @columns song_id, system_id
	 */
	const UNIQUE_SONG_SYSTEM = 'unique_song_system';

	function getSchema() { return new TableSchema('Site\\Song\\System\\DB\\SongSystemEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}