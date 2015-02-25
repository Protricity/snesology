<?php
namespace Site\Song\Review\DB;

use CPath\Data\Schema\IReadableSchema;
use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use CPath\Data\Schema\TableSchema;
use Site\DB\SiteDB as DB;
use Site\Song\Review\DB\SongReviewEntry as Entry;

/**
 * Class SongReviewTable
 * @table song_review
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SongReviewEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SongReviewEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SongReviewEntry[]
 */
class SongReviewTable extends AbstractBase implements IReadableSchema {
	const TABLE_NAME = 'song_review';
	const FETCH_CLASS = 'Site\\Song\\Review\\DB\\SongReviewEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index --name index_song_review
	 * @unique --name unique_song_review
	 */
	const COLUMN_SONG_ID = 'song_id';
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
	 * @columns song_id
	 */
	const INDEX_SONG_REVIEW = 'index_song_review';
	/**

	 * @index UNIQUE
	 * @columns song_id, account_fingerprint
	 */
	const UNIQUE_SONG_REVIEW = 'unique_song_review';
	/**

	 * @index 
	 * @columns created
	 */
	const SONG_REVIEW_CREATED_INDEX = 'song_review_created_index';

	function getSchema() { return new TableSchema('Site\\Song\\Review\\DB\\SongReviewEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}