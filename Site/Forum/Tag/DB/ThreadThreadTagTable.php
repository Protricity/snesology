<?php
namespace Site\Forum\Tag\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Forum\Tag\DB\ThreadTagEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class ThreadThreadTagTable
 * @table thread_tag
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a ThreadTagEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single ThreadTagEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of ThreadTagEntry[]
 */
class ThreadThreadTagTable extends AbstractBase {
	const TABLE_NAME = 'thread_tag';
	const FETCH_CLASS = 'Site\\Forum\\Tag\\DB\\ThreadTagEntry';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @index
	 */
	const COLUMN_THREAD_ID = 'thread_id';
	/**

	 * @column VARCHAR(64) NOT NULL
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
	 * @columns thread_id
	 */
	const THREAD_TAG_THREAD_ID_INDEX = 'thread_tag_thread_id_index';
	/**

	 * @index 
	 * @columns tag, value
	 */
	const INDEX_TAG_VALUE = 'index_tag_value';

	function getSchema() { return new TableSchema('Site\\Forum\\Tag\\DB\\ThreadTagEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}