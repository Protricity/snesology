<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Forum\Tag\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use Site\DB\SiteDB;
use Site\Forum\DB\ThreadTable;

/**
 * Class TagEntry
 * @table thread_tag
 */
class ThreadTagEntry implements IBuildable, IKeyMap, IRenderHTML
{
    const TYPE_DEFAULT = 's';
    const TYPE_STRING = 's';
    const TYPE_BOOLEAN = 'b';
    const TYPE_NUMERIC = 'n';
    const TYPE_URL = 'url';

    const TAG_URL_DOWNLOAD = 'url:download';
//    const TAG_FALACY = 'falacy';

    const JOIN_COLUMN_SONGS = 'songs';

    static $TagDefaults = array(
        "Download URL" => self::TAG_URL_DOWNLOAD,
    );

    /**
	 * @column VARCHAR(64) NOT NULL
     * @index
	 */
	protected $thread_id;

    /**
     * @column VARCHAR(64) NOT NULL
     * @index --name index_tag_value
     */
    protected $tag;

    /**
     * @column VARCHAR(256) NOT NULL
     * @index --name index_tag_value
     */
    protected $value;

    protected $songs;
    protected $albums;

    public function getThreadID() {
        return $this->thread_id;
    }

    public function getTagName() {
		return $this->tag;
	}

    public function getTagValue() {
        return $this->value;
    }


    /**
     * Map data to the key map
     * @param IKeyMapper $Map the map inst to add data to
     * @return void
     */
    function mapKeys(IKeyMapper $Map) {
        $Map->map('tag', $this->getTagName());
        $Map->map('tag-value', $this->getTagValue());
    }

    /**
     * Render request as html
     * @param IRequest $Request the IRequest inst for this render which contains the request and remaining args
     * @param IAttributes $Attr
     * @param IRenderHTML $Parent
     * @return String|void always returns void
     */
    function renderHTML(IRequest $Request, IAttributes $Attr = null, IRenderHTML $Parent = null) {
        echo RI::ni(), "<span class='tag tag-", $this->getTagName(), "'>", $this->getTagValue(), "</span>";
    }

	// Static


    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(ThreadTagTable::TABLE_NAME . '.' . ThreadTagTable::COLUMN_THREAD_ID)
            ->select(ThreadTagTable::TABLE_NAME . '.' . ThreadTagTable::COLUMN_TAG)
            ->select(ThreadTagTable::TABLE_NAME . '.' . ThreadTagTable::COLUMN_VALUE)

            ->leftJoin(ThreadTable::TABLE_NAME, ThreadTagTable::TABLE_NAME . '.' . ThreadTagTable::COLUMN_THREAD_ID, ThreadTable::TABLE_NAME . '.' . ThreadTable::COLUMN_ID)

//            ->groupBy(ThreadTagTable::TABLE_NAME . '.' . ThreadTagTable::COLUMN_TAG . ',' . ThreadTagTable::TABLE_NAME . '.' . ThreadTagTable::COLUMN_VALUE)

            ->setFetchMode(ThreadTagTable::FETCH_MODE, ThreadTagTable::FETCH_CLASS);
    }

    static function removeFromThread($Request, $threadID, $tag, $tagValue) {
        $delete = self::table()
            ->delete()
            ->where(ThreadTagTable::COLUMN_THREAD_ID, $threadID)
            ->where(ThreadTagTable::COLUMN_TAG, $tag)
            ->where(ThreadTagTable::COLUMN_VALUE, $tagValue)
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToThread(IRequest $Request, $threadID, $tag, $tagValue) {
        $inserted = self::table()->insert(array(
            ThreadTagTable::COLUMN_THREAD_ID => $threadID,
            ThreadTagTable::COLUMN_TAG => $tag,
            ThreadTagTable::COLUMN_VALUE => $tagValue,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Tag added to thread: " . $tag . '=' . $tagValue, $Request::VERBOSE);
    }

    static function table() {
        return new ThreadTagTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\ThreadThreadTagTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}