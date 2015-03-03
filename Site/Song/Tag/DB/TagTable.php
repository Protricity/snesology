<?php
namespace Site\Song\Tag\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Tag\DB\TagEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class TagTable
 * @table tag
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a TagEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single TagEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of TagEntry[]
 */
class TagTable extends AbstractBase {
	const TABLE_NAME = 'tag';
	const FETCH_CLASS = 'Site\\Song\\Tag\\DB\\TagEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_song_tag
	 */
	const COLUMN_SOURCE_ID = 'source_id';
	/**

	 * @column ENUM('song', 'album') NOT NULL
	 * @index --name index_tag_source
	 */
	const COLUMN_SOURCE_TYPE = 'source_type';
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
	 * @columns source_id, tag
	 */
	const INDEX_SONG_TAG = 'index_song_tag';
	/**

	 * @index 
	 * @columns source_type
	 */
	const INDEX_TAG_SOURCE = 'index_tag_source';
	/**

	 * @index 
	 * @columns tag, value
	 */
	const INDEX_TAG_VALUE = 'index_tag_value';

	function getSchema() { return new TableSchema('Site\\Song\\Tag\\DB\\TagEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}