<?php
namespace Site\Song\Genre\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Genre\DB\GenreEntry as Entry;
use CPath\Data\Schema\TableSchema;
use CPath\Data\Schema\IReadableSchema;

/**
 * Class GenreTable
 * @table genre
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a GenreEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a GenreEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a GenreEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single GenreEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of GenreEntry[]
 */
class GenreTable extends AbstractBase implements IReadableSchema {
	const TABLE_NAME = 'genre';
	const FETCH_CLASS = 'Site\\Song\\Genre\\DB\\GenreEntry';
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
	const GENRE_NAME_UNIQUE = 'genre_name_unique';

	function insertRow($name = null, $status = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Song\\Genre\\DB\\GenreEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}