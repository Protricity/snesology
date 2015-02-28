<?php
namespace Site\Path\DB;

use CPath\Data\Schema\PDO\AbstractPDOTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Path\DB\PathEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class PathTable
 * @table path
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a PathEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single PathEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of PathEntry[]
 */
class PathTable extends AbstractBase {
	const TABLE_NAME = 'path';
	const FETCH_CLASS = 'Site\\Path\\DB\\PathEntry';
	const SELECT_COLUMNS = 'path, title, content, status, created';
	const UPDATE_COLUMNS = 'title, content, status';
	const INSERT_COLUMNS = 'path, title, content, status, created';
	const SEARCH_COLUMNS = 'path, title, content';
	/**

	 * @column VARCHAR(128)
	 * @insert
	 * @unique
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
	/**

	 * @index UNIQUE
	 * @columns path
	 */
	const PATH_PATH_UNIQUE = 'path_path_unique';

	function insertRow($path = null, $title = null, $content = null, $status = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Path\\DB\\PathEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}