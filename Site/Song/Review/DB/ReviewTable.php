<?php
namespace Site\Song\Review\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Review\DB\ReviewEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class ReviewTable
 * @table song_review
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a ReviewEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a ReviewEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a ReviewEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single ReviewEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of ReviewEntry[]
 */
class ReviewTable extends AbstractBase {
	const TABLE_NAME = 'song_review';
	const FETCH_CLASS = 'Site\\Song\\Review\\DB\\ReviewEntry';
	const SELECT_COLUMNS = 'id';
	const SEARCH_COLUMNS = 'id';
	const PRIMARY_COLUMN = 'id';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	const COLUMN_ID = 'id';
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
	 * @unique --name unique_song_review
	 */
	const COLUMN_ACCOUNT_FINGERPRINT = 'account_fingerprint';
	/**

	 * @column VARCHAR(256) NOT NULL
	 */
	const COLUMN_REVIEW_TITLE = 'review_title';
	/**

	 * @column TEXT
	 */
	const COLUMN_REVIEW = 'review';
	/**

	 * @column INTEGER
	 */
	const COLUMN_STATUS = 'status';
	/**

	 * @index
	 * @column INTEGER
	 */
	const COLUMN_CREATED = 'created';
	/**

	 * @index 
	 * @columns source_id
	 */
	const INDEX_SONG_TAG = 'index_song_tag';
	/**

	 * @index 
	 * @columns source_type
	 */
	const INDEX_TAG_SOURCE = 'index_tag_source';
	/**

	 * @index UNIQUE
	 * @columns account_fingerprint
	 */
	const UNIQUE_SONG_REVIEW = 'unique_song_review';
	/**

	 * @index 
	 * @columns created
	 */
	const SONG_REVIEW_CREATED_INDEX = 'song_review_created_index';

	function getSchema() { return new TableSchema('Site\\Song\\Review\\DB\\ReviewEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}