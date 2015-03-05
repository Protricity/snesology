<?php
namespace Site\Account\DB;

use CPath\Data\Schema\PDO\AbstractPDOPrimaryKeyTable as AbstractBase;
use Site\DB\SiteDB as DB;
use Site\Account\DB\AccountEntry as Entry;
use CPath\Data\Schema\TableSchema;

/**
 * Class AccountTable
 * @table account
 * @method Entry insertOrUpdate($fingerprint, Array $insertData) insert or update a AccountEntry instance
 * @method Entry insertAndFetch(Array $insertData) insert and fetch a AccountEntry instance
 * @method Entry fetch($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a AccountEntry instance
 * @method Entry fetchOne($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch a single AccountEntry
 * @method Entry[] fetchAll($whereColumn, $whereValue=null, $compare='=?', $selectColumns=null) fetch an array of AccountEntry[]
 */
class AccountTable extends AbstractBase {
	const TABLE_NAME = 'account';
	const FETCH_CLASS = 'Site\\Account\\DB\\AccountEntry';
	const SELECT_COLUMNS = 'fingerprint, email, name, created, invite_fingerprint';
	const UPDATE_COLUMNS = 'public_key, challenge, answer';
	const INSERT_COLUMNS = 'email, name, public_key, challenge, answer, created';
	const SEARCH_COLUMNS = 'fingerprint, email, name, invite_fingerprint';
	const PRIMARY_COLUMN = 'fingerprint';
	/**

	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	const COLUMN_FINGERPRINT = 'fingerprint';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @select
	 * @insert
	 * @unique
	 * @search
	 */
	const COLUMN_EMAIL = 'email';
	/**

	 * @column VARCHAR(64) NOT NULL
	 * @select
	 * @insert
	 * @unique
	 * @search
	 */
	const COLUMN_NAME = 'name';
	/**

	 * @column TEXT
	 * @insert
	 * @update
	 */
	const COLUMN_PUBLIC_KEY = 'public_key';
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
	/**

	 * @column INT
	 * @select
	 * @insert
	 */
	const COLUMN_CREATED = 'created';
	/**

	 * @column VARCHAR(64)
	 * @select
	 * @search
	 */
	const COLUMN_INVITE_FINGERPRINT = 'invite_fingerprint';
	/**

	 * @index UNIQUE
	 * @columns email
	 */
	const ACCOUNT_EMAIL_UNIQUE = 'account_email_unique';
	/**

	 * @index UNIQUE
	 * @columns name
	 */
	const ACCOUNT_NAME_UNIQUE = 'account_name_unique';

	function insertRow($email = null, $name = null, $public_key = null, $challenge = null, $answer = null, $created = null) { 
		return $this->insert(get_defined_vars());
	}

	function getSchema() { return new TableSchema('Site\\Account\\DB\\AccountEntry'); }

	private $mDB = null;
	function getDatabase() { return $this->mDB ?: $this->mDB = new DB(); }
}