<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Path\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Request\IRequest;
use Site\DB\SiteDB;


/**
 * Class PathEntry
 * @table path
 */
class PathEntry implements IBuildable, IKeyMap
{
    const STATUS_ACTIVE =            0x01;
    const STATUS_SESSION_REQUIRED =  0x02;

    static $StatusOptions = array(
        "Active" =>                 self::STATUS_ACTIVE,
        "Session Required" =>       self::STATUS_SESSION_REQUIRED,
    );

    const FIELD_PASSPHRASE = 'passphrase';

	const JSON_PASSPHRASE_COMMENTS = '{
	"#comments": [
		"/**",
		" * This is the contents of your decrypted path challenge.",
		" * If you are reading this, that means you successfully decrypted",
		" * your path challenge and authenticated your public key identity.",
		" * \'passphrase\':\'[your challenge passphrase]\'",
		" * To Authenticate: enter the following JSON value as the challenge answer:",
		" */"]
}';

    /**
     * @column VARCHAR(128)
     * @insert
     * @unique
     * @select
     * @search
     */
    protected $path;

	/**
	 * @column VARCHAR(128)
     * @insert
     * @update
     * @select
	 * @search
	 */
	protected $title;

    /**
     * @column TEXT
     * @select
     * @update
     * @insert
     * @search
     */
    protected $content;

    /**
     * @column INT
     * @select
     * @insert
     * @update
     */
    protected $status;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $created;

    /**
     * @return mixed
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getContent() {
        return $this->content;
    }

	public function getCreatedTimestamp() {
		return $this->created;
	}

    public function getStatusFlags() {
        return (int) $this->status;
    }

    public function hasFlags($flags) {
        return $this->getStatusFlags() & $flags;
    }

    public function getStatusList() {
        $statusList = array();
        $statusFlags = $this->getStatusFlags();
        foreach(self::$StatusOptions as $name => $flag) {
            if ($statusFlags & $flag) {
                $statusList[] = substr($name, 7);
            }
        }
        return $statusList ?: array("Inactive");
    }


    public function update(IRequest $Request, $title=null, $description=null, $status=null) {
        $Update = self::table()
            ->update();

        $title === null ?: $Update->update(PathTable::COLUMN_TITLE, $title);
        $description === null ?: $Update->update(PathTable::COLUMN_CONTENT, $description);
        $status === null ?: $Update->update(PathTable::COLUMN_STATUS, $status);

        $Update->where(PathTable::COLUMN_PATH, $this->getPath());

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
    }

    public function activate(IRequest $Request) {
        $status = $this->status | self::STATUS_ACTIVE;
        self::table()
            ->update(PathTable::COLUMN_STATUS, $status)
            ->where(PathTable::COLUMN_PATH, $this->getPath())
            ->execute($Request);
    }

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
        $Map->map('path', $this->getPath());
        $Map->map('title', $this->getTitle());
        $Map->map('content', $this->getContent());
        $Map->map('created', $this->getCreatedTimestamp());
        $Map->map('status', implode(', ', $this->getStatusList()));
    }

    // Static

    /** @var PathEntry[] */
    static $SubPaths = null;

    public static function trySubPath(IRequest $Request, $relativePath) {
        if(self::$SubPaths === null) {
            self::$SubPaths = self::fetchSubPaths($Request);
        }

        $fullPath = $Request->getPath() . $relativePath;

        foreach(self::$SubPaths as $i => $SubPath) {
            if($SubPath->getPath() === $fullPath) {
                unset(self::$SubPaths[$i]);
                return $SubPath;
            }
        }

        return null;
    }

    /**
     * @param IRequest $Request
     * @param String|null $requestPath
     * @return PathEntry[]
     */
    static function fetchSubPaths(IRequest $Request, $requestPath=null) {
        $requestPath ?: $requestPath = $Request->getPath();
        $Query = self::query()
            ->where(PathTable::COLUMN_PATH, $requestPath . '%', ' LIKE ?');
        return $Query->fetchAll();
    }

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(PathTable::TABLE_NAME . '.' . PathTable::COLUMN_PATH)
            ->select(PathTable::TABLE_NAME . '.' . PathTable::COLUMN_TITLE)
            ->select(PathTable::TABLE_NAME . '.' . PathTable::COLUMN_CONTENT)
            ->select(PathTable::TABLE_NAME . '.' . PathTable::COLUMN_CREATED)
            ->select(PathTable::TABLE_NAME . '.' . PathTable::COLUMN_STATUS)
            ->setFetchMode(PathTable::FETCH_MODE, PathTable::FETCH_CLASS);
    }


    static function create(IRequest $Request, $path, $title, $content, $status) {
		$inserted = self::table()->insert(array(
            PathTable::COLUMN_PATH => $path,
            PathTable::COLUMN_TITLE => $title,
			PathTable::COLUMN_CONTENT => $content,
            PathTable::COLUMN_STATUS => $status,
            PathTable::COLUMN_CREATED => time(),
		))
			->execute($Request);

		if(!$inserted)
			throw new \InvalidArgumentException("Could not insert " . __CLASS__);

		$Request->log("New Path Entry Inserted: " . $path, $Request::VERBOSE);

		return $path;
	}

	static function delete($Request, $path) {
		$delete = self::table()->delete(PathTable::COLUMN_PATH, $path)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

    /**
     * @param $path
     * @param string $compare
     * @return PathEntry
     */
	static function get($path, $compare = '=?') {
		return self::table()->fetchOne(PathTable::COLUMN_PATH, $path, $compare);
	}

	/**
	 * @return PathTable
	 */
	static function table() {
		return new PathTable();
	}

	/**
	 * Handle this request and render any content
	 * @param IBuildRequest $Request the build request inst for this build session
	 * @return void
	 * @build --disable 0
	 * Note: Use doctag 'build' with '--disable 1' to have this IBuildable class skipped during a build
	 */
	static function handleBuildStatic(IBuildRequest $Request) {
		$Schema = new TableSchema(__CLASS__);
		$DB = new SiteDB();
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\PathTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}