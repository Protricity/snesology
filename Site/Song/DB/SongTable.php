<?php
namespace Site\Song\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\DB\SongEntry as Entry;
use CPath\Data\Schema\TableSchema;
use CPath\Data\Schema\IReadableSchema;

/**
 * Class SongTable
 * @table song
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a SongEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a SongEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a SongEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single SongEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of SongEntry[]
 */
class SongTable extends AbstractBase implements IReadableSchema {
	const TABLE_NAME = 'song';
	const FETCH_CLASS = 'Site\\Song\\DB\\SongEntry';
	const SELECT_COLUMNS = 'id, title, description, status, created';
	const INSERT_COLUMNS = 'title, description, status, created';
	const SEARCH_COLUMNS = 'id, title';
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
	 * @search
	 */
	const COLUMN_TITLE = 'title';
	/**

	 * @column TEXT
	 * @select
	 * @insert
	 */
	const COLUMN_DESCRIPTION = 'description';
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

	function insertRow($title = null, $description = null, $status = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Song\\DB\\SongEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}