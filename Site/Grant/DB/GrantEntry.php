<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Grant\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\TableSchema;
use Site\DB\SiteDB;


/**
 * Class GrantEntry
 * @table grant
 */
class GrantEntry implements IBuildable
{
	/**
	 * @column INTEGER PRIMARY KEY AUTOINCREMENT
	 * @select
	 */
	protected $id;

	/**
	 * @column CHAR(40)
	 * @index --name grant_lookup
	 * @unique --name grant_unique
	 * @select
	 * @insert
	 */
	protected $grant_fingerprint;

	/**
	 * @column CHAR(128)
	 * @unique --name grant_unique
	 * @select
	 * @insert
	 */
	protected $content_key;

	/**
	 * @column BLOB
	 * @update
	 * @insert
	 */
	protected $content;

	/**
	 * @column BLOB
	 * @update
	 * @insert
	 */
	protected $signature;

	/**
	 * @column TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	 * @select
	 */
	protected $created;

	/**
	 * @column CHAR(40)
	 * @select
	 * @insert
	 */
	protected $class;

	public function getId() {
		return $this->id;
	}

	public function getClass() {
		return $this->class;
	}

	public function getContentKey() {
		return $this->content_key;
	}

	public function getGrantFingerprint() {
		return $this->grant_fingerprint;
	}

	public function loadContent(&$signature=null) {
		$GrantTable = new GrantTable();
		list($content, $signature) =  $GrantTable->select(array($GrantTable::COLUMN_CONTENT, $GrantTable::COLUMN_SIGNATURE))
			->where($GrantTable::COLUMN_ID, $this->getID())
			->fetch();
		return $content;
	}

	// Static

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 0
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$Schema = new TableSchema(__CLASS__);
		$ClassWriter = new PDOTableClassWriter(new SiteDB(), __NAMESPACE__ . '\GrantTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
	}
}