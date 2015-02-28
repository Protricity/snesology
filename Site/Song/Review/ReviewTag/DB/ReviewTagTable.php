<?php
namespace Site\Song\Review\ReviewTag\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Review\ReviewTag\DB\ReviewTagEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class ReviewTagTable
 * @table song_review_tag
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a ReviewTagEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single ReviewTagEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of ReviewTagEntry[]
 */
class ReviewTagTable extends AbstractBase {
	const TABLE_NAME = 'song_review_tag';
	const FETCH_CLASS = 'Site\\Song\\Review\\ReviewTag\\DB\\ReviewTagEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_review_tag
	 */
	const COLUMN_SONG_ID = 'song_id';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_review_tag
	 */
	const COLUMN_ACCOUNT_FINGERPRINT = 'account_fingerprint';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_review_tag_value
	 */
	const COLUMN_TAG = 'tag';
	/**

	 * @column VARCHAR(256) NOT NULL
	 * @index --name index_review_tag_value
	 */
	const COLUMN_VALUE = 'value';
	/**

	 * @index 
	 * @columns song_id, account_fingerprint
	 */
	const INDEX_REVIEW_TAG = 'index_review_tag';
	/**

	 * @index 
	 * @columns tag, value
	 */
	const INDEX_REVIEW_TAG_VALUE = 'index_review_tag_value';

	function getSchema() { return new TableSchema('Site\\Song\\Review\\ReviewTag\\DB\\ReviewTagEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}