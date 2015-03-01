<?php
namespace Site\Song\Tag\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Tag\DB\SongTagEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class SongTagTable
 * @table song_tag
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SongTagEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SongTagEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SongTagEntry[]
 */
class SongTagTable extends AbstractBase {
	const TABLE_NAME = 'song_tag';
	const FETCH_CLASS = 'Site\\Song\\Tag\\DB\\SongTagEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_song_tag
	 */
	const COLUMN_SONG_ID = 'song_id';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_song_tag
	 * @index --name index_tag_value
	 */
	const COLUMN_TAG = 'tag';
	/**

	 * @column VARCHAR(256) NOT NULL
	 * @index --name index_tag_value
	 */
	const COLUMN_VALUE = 'value';
	/**

	 * @index 
	 * @columns song_id, tag
	 */
	const INDEX_SONG_TAG = 'index_song_tag';
	/**

	 * @index 
	 * @columns tag, value
	 */
	const INDEX_TAG_VALUE = 'index_tag_value';

	function getSchema() { return new TableSchema('Site\\Song\\Tag\\DB\\SongTagEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}