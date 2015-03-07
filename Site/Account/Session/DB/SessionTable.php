<?php
namespace Site\Account\Session\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Account\Session\DB\SessionEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class SessionTable
 * @table session
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a SessionEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a SessionEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SessionEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SessionEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SessionEntry[]
 */
class SessionTable extends AbstractBase {
	const TABLE_NAME = 'session';
	const FETCH_CLASS = 'Site\\Account\\Session\\DB\\SessionEntry';
	const SELECT_COLUMNS = 'id, fingerprint, created, status';
	const UPDATE_COLUMNS = 'fields';
	const INSERT_COLUMNS = 'fingerprint, fields, created, status';
	const SEARCH_COLUMNS = 'id, fingerprint';
	const PRIMARY_COLUMN = 'id';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	const COLUMN_ID = 'id';
	/**

	 * @column VARCHAR(64)
	 * @select
	 * @insert
	 * @search
	 */
	const COLUMN_FINGERPRINT = 'fingerprint';
	/**

	 * @column TEXT
	 * @insert
	 * @update
	 */
	const COLUMN_FIELDS = 'fields';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_CREATED = 'created';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_STATUS = 'status';

	function insertRow($fingerprint = null, $fields = null, $created = null, $status = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Account\\Session\\DB\\SessionEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}