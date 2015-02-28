<?php
namespace Site\Relay\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Relay\DB\RelayLogEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class RelayLogTable
 * @table relay_log
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a RelayLogEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single RelayLogEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of RelayLogEntry[]
 */
class RelayLogTable extends AbstractBase {
	const TABLE_NAME = 'relay_log';
	const FETCH_CLASS = 'Site\\Relay\\DB\\RelayLogEntry';
	const SELECT_COLUMNS = 'path, account_fingerprint, log, created';
	const UPDATE_COLUMNS = 'path, account_fingerprint, log';
	const INSERT_COLUMNS = 'path, account_fingerprint, log, created';
	const SEARCH_COLUMNS = 'path, account_fingerprint, log';
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
	 * @search
	 */
	const COLUMN_LOG = 'log';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_CREATED = 'created';

	function insertRow($path = null, $account_fingerprint = null, $log = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Relay\\DB\\RelayLogEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}