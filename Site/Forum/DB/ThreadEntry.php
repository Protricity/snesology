<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Forum\DB;
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\HTMLConfig;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use Site\Account\DB\AccountTable;
use Site\DB\SiteDB;
use Site\Forum\ViewThread;


/**
 * Class ThreadEntry
 * @table thread
 */
class ThreadEntry implements IBuildable, IKeyMap, IRenderHTML
{
    const STATUS_ACTIVE =            0x01;
    const ID_PREFIX = 'T';
    const JOIN_ACCOUNT_NAME = 'account_name';

    static $StatusOptions = array(
        "Active" =>                 self::STATUS_ACTIVE,
    );

    /**
     * @column VARCHAR(64) PRIMARY KEY
     * @insert
     * @select
     * @search
     */
    protected $id;

    /**
     * @column VARCHAR(128)
     * @insert
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
     * @column VARCHAR(64)
     * @insert
     * @update
     * @select
     * @search
     */
    protected $account_fingerprint;

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

    protected $account_name;

    public function getID() {
        return $this->id;
    }

    public function getPath() {
        return $this->path;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getAccountFingerprint() {
        return $this->account_fingerprint;
    }

    public function getAccountName() {
        return $this->account_name;
    }

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

        $title === null ?: $Update->update(ThreadTable::COLUMN_TITLE, $title);
        $description === null ?: $Update->update(ThreadTable::COLUMN_CONTENT, $description);
        $status === null ?: $Update->update(ThreadTable::COLUMN_STATUS, $status);

        $Update->where(ThreadTable::COLUMN_ID, $this->getID());

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
    }

    public function activate(IRequest $Request) {
        $status = $this->status | self::STATUS_ACTIVE;
        self::table()
            ->update(ThreadTable::COLUMN_STATUS, $status)
            ->where(ThreadTable::COLUMN_ID, $this->getID())
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
        $Map->map('id', $this->getID());
        $Map->map('path', $this->getPath());
        $Map->map('account', $this->getAccountFingerprint(), $this->getAccountName());
        $Map->map('title', $this->getTitle());
        $Map->map('content', $this->getContent());
        $Map->map('created', $this->getCreatedTimestamp());
        $Map->map('status', implode(', ', $this->getStatusList()));
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        echo RI::ni(1), "<li class='post'>";

        if($this->getTitle()) {
            echo RI::ni(2), "<h3 class='post-title'>";
            echo RI::ni(3), HTMLConfig::renderNamedValue('thread-path', $this->getPath(), $this->getTitle());
            echo RI::ni(2), "</h3>";
        }

        echo RI::ni(2), "<div class='post-content'>";
        echo RI::ni(3), $this->getContent();
        echo RI::ni(2), "</div>";

        echo RI::ni(2), "<span class='post-account'> by ";
        echo RI::ni(3), HTMLConfig::renderNamedValue('account', $this->getAccountFingerprint(), $this->getAccountName());
        echo RI::ni(2), "</span>";

        echo RI::ni(2), "<span class='post-created-on'>";
        echo RI::ni(3), HTMLConfig::renderNamedValue('created', $this->getCreatedTimestamp());
        echo RI::ni(2), "</span>";

        echo RI::ni(2), "<br/>";
        echo RI::ni(2), "<span class='post-action'>";
        echo RI::ni(3), HTMLConfig::renderNamedValue('url', ViewThread::getBranchRequestURL($this->getPath()), 'branch');
        echo RI::ni(3), HTMLConfig::renderNamedValue('url', ViewThread::getReplyRequestURL($this->getPath()), 'reply');
        echo RI::ni(2), "</span>";

        echo RI::ni(1), "</li>";
    }

    // Static

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_ID)
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_PATH)
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_ACCOUNT_FINGERPRINT)
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_TITLE)
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_CONTENT)
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_CREATED)
            ->select(ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_STATUS)

            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_NAME, self::JOIN_ACCOUNT_NAME)
            ->leftJoin(AccountTable::TABLE_NAME, AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_FINGERPRINT, ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_ACCOUNT_FINGERPRINT)

            ->setFetchMode(ThreadTable::FETCH_MODE, ThreadTable::FETCH_CLASS);
    }


    static function create(IRequest $Request, $accountFingerprint, $path, $title, $content, $status=0) {
        $id = strtoupper(uniqid(self::ID_PREFIX));

        $inserted = self::table()->insert(array(
            ThreadTable::COLUMN_ID => $id,
            ThreadTable::COLUMN_ACCOUNT_FINGERPRINT => $accountFingerprint,
            ThreadTable::COLUMN_PATH => $path,
            ThreadTable::COLUMN_TITLE => $title,
			ThreadTable::COLUMN_CONTENT => $content,
            ThreadTable::COLUMN_STATUS => $status,
            ThreadTable::COLUMN_CREATED => time(),
		))
			->execute($Request);

		if(!$inserted)
			throw new \InvalidArgumentException("Could not insert " . __CLASS__);

		$Request->log("New Thread Entry Inserted: " . $path, $Request::VERBOSE);

		return $id;
	}

	static function delete($Request, $id) {
		$delete = self::table()->delete(ThreadTable::COLUMN_ID, $id)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

    /**
     * @param $id
     * @return ThreadEntry
     */
	static function get($id) {
		return self::table()->fetchOne(ThreadTable::COLUMN_ID, $id);
	}

	/**
	 * @return ThreadTable
	 */
	static function table() {
		return new ThreadTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\ThreadTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}