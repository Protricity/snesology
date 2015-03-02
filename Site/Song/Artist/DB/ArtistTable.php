<?php
namespace Site\Song\Artist\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Artist\DB\ArtistEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class ArtistTable
 * @table artist
 * @method Entry insertOrUpdate($name, Array $insertData) insert or update a ArtistEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a ArtistEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a ArtistEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single ArtistEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of ArtistEntry[]
 */
class ArtistTable extends AbstractBase {
	const TABLE_NAME = 'artist';
	const FETCH_CLASS = 'Site\\Song\\Artist\\DB\\ArtistEntry';
	const SELECT_COLUMNS = 'name, description, url, status, created';
	const INSERT_COLUMNS = 'description, status, created';
	const SEARCH_COLUMNS = 'name';
	const PRIMARY_COLUMN = 'name';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @unique
	 * @select
	 * @search
	 */
	const COLUMN_NAME = 'name';
	/**

	 * @column TEXT
	 * @select
	 * @insert
	 */
	const COLUMN_DESCRIPTION = 'description';
	/**

	 * @column VARCHAR(128)
	 * @select
	 */
	const COLUMN_URL = 'url';
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
	const ARTIST_NAME_UNIQUE = 'artist_name_unique';

	function insertRow($description = null, $status = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Song\\Artist\\DB\\ArtistEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}