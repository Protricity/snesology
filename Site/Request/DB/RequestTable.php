<?php
namespace Site\Request\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Request\DB\RequestEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class RequestTable
 * @table request
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a RequestEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a RequestEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a RequestEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single RequestEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of RequestEntry[]
 */
class RequestTable extends AbstractBase {
	const TABLE_NAME = 'request';
	const FETCH_CLASS = 'Site\\Request\\DB\\RequestEntry';
	const SELECT_COLUMNS = 'id, path, account_fingerprint, request, log, created';
	const UPDATE_COLUMNS = 'path, account_fingerprint, request, log';
	const INSERT_COLUMNS = 'path, account_fingerprint, request, log, created';
	const SEARCH_COLUMNS = 'id, path, account_fingerprint, log';
	const PRIMARY_COLUMN = 'id';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	const COLUMN_ID = 'id';
	/**

	 * @column VARCHAR(128)
	 * @insert
	 * @update
	 * @select
	 * @search
	 */
	const COLUMN_PATH = 'path';
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
	 */
	const COLUMN_REQUEST = 'request';
	/**

	 * @column TEXT
	 * @select
	 * @update
	 * @insert
	 * @search
	 */
	const COLUMN_LOG = 'log';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_CREATED = 'created';

	function insertRow($path = null, $account_fingerprint = null, $request = null, $log = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Request\\DB\\RequestEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}