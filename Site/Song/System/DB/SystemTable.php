<?php
namespace Site\Song\System\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\System\DB\SystemEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class SystemTable
 * @table system
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a SystemEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a SystemEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SystemEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SystemEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SystemEntry[]
 */
class SystemTable extends AbstractBase {
	const TABLE_NAME = 'system';
	const FETCH_CLASS = 'Site\\Song\\System\\DB\\SystemEntry';
	const SELECT_COLUMNS = 'id, name, status, created';
	const INSERT_COLUMNS = 'name, status, created';
	const SEARCH_COLUMNS = 'id, name';
	const PRIMARY_COLUMN = 'id';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	const COLUMN_ID = 'id';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @select
	 * @insert
	 * @unique
	 * @search
	 */
	const COLUMN_NAME = 'name';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_STATUS = 'status';
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_CREATED = 'created';
	/**

	 * @index UNIQUE
	 * @columns name
	 */
	const SYSTEM_NAME_UNIQUE = 'system_name_unique';

	function insertRow($name = null, $status = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Song\\System\\DB\\SystemEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}