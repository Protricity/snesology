<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\DB;

use CPath\Data\Schema\IReadableSchema;
use CPath\Data\Schema\IRepairableSchema;
use CPath\Data\Schema\IWritableSchema;
use CPath\Data\Schema\PDO\AbstractPDOTable;
use PDO;
use Site\Account\DB\AccountTable;
use Site\Request\DB\RequestTable;
use Site\Song\Artist\DB\ArtistTable;
use Site\Song\DB\SongTable;
use Site\Song\Genre\DB\GenreTable;
use Site\Song\Review\DB\ReviewTable;
use Site\Song\Review\ReviewTag\DB\ReviewTagTable;
use Site\Song\System\DB\SystemTable;
use Site\Song\Tag\DB\TagTable;

class SiteDB extends \PDO implements IReadableSchema, IRepairableSchema
{
	public function __construct($options = null) {
		$host     = DBConfig::$DB_HOST;
		$dbname   = DBConfig::$DB_NAME;
		$port     = DBConfig::$DB_PORT;
		$username = DBConfig::$DB_USERNAME;
		$passwd   = DBConfig::$DB_PASSWORD;
//		CREATE DATABASE `processor` /*!40100 COLLATE 'utf8_general_ci' */

		$options ?: $options = array(
			\PDO::ATTR_PERSISTENT => true,
		);

		try {
			parent::__construct("mysql:host={$host};port={$port};dbname={$dbname}", $username, $passwd, $options);
		} catch (\PDOException $ex) {
			if(strpos($ex->getMessage(), 'Unknown database') !== false) {
				$PDO = new PDO("mysql:host={$host};port={$port}", $username, $passwd, $options);
				$PDO->query("create database {$dbname}");
				$PDO->query("use {$dbname}");
				parent::__construct("mysql:host={$host};port={$port};dbname={$dbname}", $username, $passwd, $options);
			} else {
				throw $ex;
			}
		}

		$this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//		$this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
	}

	/**
	 * Write schema to a writable source
	 * @param IWritableSchema $DB
	 */
	public function writeSchema(IWritableSchema $DB) {
		foreach(
			array(
				new AccountTable(),
                new SongTable(),
                new ReviewTable(),
                new ReviewTagTable(),
                new TagTable(),
                new SystemTable(),
                new GenreTable(),
                new ArtistTable(),
                new RequestTable(),
			) as $Table) {
			/** @var AbstractPDOTable $Table */
			$Table->writeSchema($DB);
		}
	}

	/**
	 * Attempt to repair a writable schema
	 * @param IWritableSchema $DB
	 * @param \Exception $ex
	 */
	public function repairSchema(IWritableSchema $DB, \Exception $ex) {
		$this->writeSchema($DB);
	}
}