<?php
namespace Site\Forum\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Forum\DB\ThreadEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class ThreadTable
 * @table thread
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a ThreadEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a ThreadEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a ThreadEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single ThreadEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of ThreadEntry[]
 */
class ThreadTable extends AbstractBase {
	const TABLE_NAME = 'thread';
	const FETCH_CLASS = 'Site\\Forum\\DB\\ThreadEntry';
	const SELECT_COLUMNS = 'id, path, title, account_fingerprint, content, status, created';
	const UPDATE_COLUMNS = 'title, account_fingerprint, content, status';
	const INSERT_COLUMNS = 'id, path, title, account_fingerprint, content, status, created';
	const SEARCH_COLUMNS = 'id, path, title, account_fingerprint, content';
	const PRIMARY_COLUMN = 'id';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @insert
	 * @select
	 * @search
	 */
	const COLUMN_ID = 'id';
	/**

	 * @column VARCHAR(128)
	 * @insert
	 * @select
	 * @search
	 */
	const COLUMN_PATH = 'path';
	/**

	 * @column VARCHAR(128)
	 * @insert
	 * @update
	 * @select
	 * @search
	 */
	const COLUMN_TITLE = 'title';
	/**

	 * @column VARCHAR(64)
	 * @insert
	 * @update
	 * @select
	 * @search
	 */
	const COLUMN_ACCOUNT_FINGERPRINT = 'account_fingerprint';
	/**

	 * @column TEXT
	 * @select
	 * @update
	 * @insert
	 * @search
	 */
	const COLUMN_CONTENT = 'content';
	/**

	 * @column INT
	 * @select
	 * @insert
	 * @update
	 */
	const COLUMN_STATUS = 'status';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_CREATED = 'created';

	function insertRow($id = null, $path = null, $title = null, $account_fingerprint = null, $content = null, $status = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Forum\\DB\\ThreadEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}