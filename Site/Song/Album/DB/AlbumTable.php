<?php
namespace Site\Song\Album\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Song\Album\DB\AlbumEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class AlbumTable
 * @table album
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a AlbumEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a AlbumEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a AlbumEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single AlbumEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of AlbumEntry[]
 */
class AlbumTable extends AbstractBase {
	const TABLE_NAME = 'album';
	const FETCH_CLASS = 'Site\\Song\\Album\\DB\\AlbumEntry';
	const SELECT_COLUMNS = 'id, title, description, status, created';
	const UPDATE_COLUMNS = 'challenge, answer';
	const INSERT_COLUMNS = 'title, description, status, created, challenge, answer';
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
	/**

	 * @column TEXT
	 * @insert
	 * @update
	 */
	const COLUMN_CHALLENGE = 'challenge';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @insert
	 * @update
	 */
	const COLUMN_ANSWER = 'answer';

	function insertRow($title = null, $description = null, $status = null, $created = null, $challenge = null, $answer = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Song\\Album\\DB\\AlbumEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}