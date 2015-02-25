<?php
namespace Site\Grant\DB;

use CPath\Data\Schema\IReadableSchema;
use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use CPath\Data\Schema\TableSchema;
use Site\DB\SiteDB as DB;
use Site\Grant\DB\GrantEntry as Entry;

/**
 * Class GrantTable
 * @table grant
 * @method Entry insertOrUpdate($id, Array $insertData) insert or update a GrantEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a GrantEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a GrantEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single GrantEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of GrantEntry[]
 */
class GrantTable extends AbstractBase implements IReadableSchema {
	const TABLE_NAME = 'grant';
	const FETCH_CLASS = 'Site\\Grant\\DB\\GrantEntry';
	const SELECT_COLUMNS = 'id, grant_fingerprint, content_key, created, class';
	const UPDATE_COLUMNS = 'content, signature';
	const INSERT_COLUMNS = 'grant_fingerprint, content_key, content, signature, class';
	const PRIMARY_COLUMN = 'id';
	/**

	 * @column INTEGER PRIMARY KEY AUTOINCREMENT
	 * @select
	 */
	const COLUMN_ID = 'id';
	/**

	 * @column CHAR(40)
	 * @index --name grant_lookup
	 * @unique --name grant_unique
	 * @select
	 * @insert
	 */
	const COLUMN_GRANT_FINGERPRINT = 'grant_fingerprint';
	/**

	 * @column CHAR(128)
	 * @unique --name grant_unique
	 * @select
	 * @insert
	 */
	const COLUMN_CONTENT_KEY = 'content_key';
	/**

	 * @column BLOB
	 * @update
	 * @insert
	 */
	const COLUMN_CONTENT = 'content';
	/**

	 * @column BLOB
	 * @update
	 * @insert
	 */
	const COLUMN_SIGNATURE = 'signature';
	/**

	 * @column TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	 * @select
	 */
	const COLUMN_CREATED = 'created';
	/**

	 * @column CHAR(40)
	 * @select
	 * @insert
	 */
	const COLUMN_CLASS = 'class';
	/**

	 * @index 
	 * @columns grant_fingerprint
	 */
	const GRANT_LOOKUP = 'grant_lookup';
	/**

	 * @index UNIQUE
	 * @columns grant_fingerprint, content_key
	 */
	const GRANT_UNIQUE = 'grant_unique';

	function insertRow($grant_fingerprint = null, $content_key = null, $content = null, $signature = null, $class = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Grant\\DB\\GrantEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}