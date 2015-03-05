<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\Review\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Request\IRequest;
use CPath\UnitTest\ITestable;
use CPath\UnitTest\IUnitTestRequest;
use Site\Config;
use Site\DB\SiteDB;
use Site\Song\Review\ReviewTag\DB\ReviewTagEntry;
use Site\Song\Review\ReviewTag\DB\ReviewTagTable;

/**
 * Class ReviewEntry
 * @table song_review
 */
class ReviewEntry implements IBuildable, IKeyMap
{
    const STATUS_PUBLISHED =            0x000001;

    const STATUS_WRITE_UP =             0x000010;
    const STATUS_CRITIQUE =             0x000020;
    const JOIN_COLUMN_TAGS = 'tags';
    const ID_PREFIX = 'R';

    static $StatusOptions = array(
        "Published" =>              self::STATUS_PUBLISHED,

        "Write-Up" =>               self::STATUS_WRITE_UP,
        "Critique" =>               self::STATUS_CRITIQUE,
    );

    /**
     * @column VARCHAR(64) PRIMARY KEY
     * @select
     * @search
     */
    protected $id;

    /**
     * @column VARCHAR(64) NOT NULL
     * @index --name index_song_tag
     * @unique --name unique_song_review
     */
    protected $source_id;

    /**
     * @column ENUM('song', 'album') NOT NULL
     * @index --name index_tag_source
     */
    protected $source_type;

    /**
     * @column VARCHAR(64) NOT NULL
     * @unique --name unique_song_review
     */
    protected $account_fingerprint;

    /**
     * @column VARCHAR(256) NOT NULL
     */
    protected $review_title;

    /**
     * @column TEXT
     */
    protected $review;

    /**
     * @column INTEGER
     */
    protected $status;

    /**
     * @index
     * @column INTEGER
     */
    protected $created;

    protected $tags;


    public function getID() {
        return $this->id;
    }

    public function getSourceID() {
		return $this->source_id;
	}

    public function getSourceType() {
        return $this->source_type;
    }

    public function getAccountFingerprint() {
        return $this->account_fingerprint;
    }

    public function getReviewTitle() {
        return $this->review_title;
    }

    /**
     * @return mixed
     */
    public function getReview() {
        return $this->review;
    }

    public function getStatusFlags() {
        return (int) $this->status;
    }

    public function getCreatedTimestamp() {
        return $this->created;
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
        return $statusList ?: array("Unpublished");
    }

    public function getTagList() {
        $tags = explode('||', $this->tags);
        $tagList = array();
        foreach($tags as &$tag) {
            list($key, $value) = explode('::', $tag);
            if($key)
                $tagList[$key] = $value;
        }
        return $tagList;
    }

    public function addTag($Request, $tagName, $tagValue) {
        ReviewTagEntry::addToReview($Request, $this->getID(), $this->getAccountFingerprint(), $tagName, $tagValue);
    }

    public function removeTag($Request, $tagName, $tagValue) {
        ReviewTagEntry::removeFromReview($Request, $this->getID(), $this->getAccountFingerprint(), $tagName, $tagValue);
    }

    public function removeAllTags($Request, $tagName) {
        ReviewTagEntry::removeFromReview($Request, $this->getID(), $this->getAccountFingerprint(), $tagName, '%');
    }

    function update(IRequest $Request, $review=null, $reviewTitle=null, $status=null) {
        $Update = self::table()
            ->update();

        $review === null ?: $Update->update(ReviewTable::COLUMN_REVIEW, $review);
        $reviewTitle === null ?: $Update->update(ReviewTable::COLUMN_REVIEW_TITLE, $reviewTitle);
        $status === null ?: $Update->update(ReviewTable::COLUMN_STATUS, $status);

        $Update->where(ReviewTable::COLUMN_ID, $this->getID());
        $Update->where(ReviewTable::COLUMN_ACCOUNT_FINGERPRINT, $this->getAccountFingerprint());

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
        $Request->log("Review updated for song: " . $this->getSourceID(), $Request::VERBOSE);
    }

    /**
     * Map data to the key map
     * @param IKeyMapper $Map the map inst to add data to
     * @return void
     */
    function mapKeys(IKeyMapper $Map) {
        $Map->map('id', $this->getID());
        $Map->map('title', $this->getReviewTitle());
        $Map->map('song-review', $this->getReview());
        $Map->map('source-id', $this->getSourceID());
        $Map->map('source-type', $this->getSourceType());
        $Map->map('review-fingerprint', $this->getAccountFingerprint());
    }

    public function getFormattedReview() {
        $review = $this->getReview();
        $ri = "\n" . RI::get()->indent();
        $review = '<p>' . str_replace("\n", "</p>{$ri}<p>", $review) . '</p>';
        foreach(Config::$AllowedTags as $tag) {
            $review = str_replace('&#60;' . $tag . '&#62;', '<' . $tag . '>', $review);
            $review = str_replace('&#60;/' . $tag . '&#62;', '</' . $tag . '>', $review);
        }
        return $review;
    }

	// Static

    /**
     * @param $sourceID
     * @param int $count
     * @param string $type
     * @return PDOSelectBuilder
     */
    public static function getLast($sourceID, $count=null, $type='song') {
        $count ?: $count = 10;
        return self::query()
            ->where(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_SOURCE_ID, $sourceID)
            ->where(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_SOURCE_TYPE, $type)
            ->orderBy(ReviewTable::COLUMN_CREATED, "DESC")
            ->limit($count);
    }

    /**
     * @param $sourceID
     * @param $accountFingerprint
     * @param string $type
     * @return ReviewEntry
     */
    public static function fetch($sourceID, $accountFingerprint, $type='song') {
        $Query = self::query()
            ->where(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_SOURCE_ID, $sourceID)
            ->where(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_SOURCE_TYPE, $type)
            ->where(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_ACCOUNT_FINGERPRINT, $accountFingerprint);
        return $Query->fetch();
    }


    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_ID)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_SOURCE_ID)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_SOURCE_TYPE)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_ACCOUNT_FINGERPRINT)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_REVIEW_TITLE)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_REVIEW)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_CREATED)
            ->select(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_STATUS)

            ->select('GROUP_CONCAT(DISTINCT CONCAT(' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_TAG . ', "::", ' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_VALUE . ') SEPARATOR "||")', self::JOIN_COLUMN_TAGS)
            ->leftJoin(ReviewTagTable::TABLE_NAME . ' ON ' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_REVIEW_ID . ' = ' . ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_ID)

            ->groupBy(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_ID)

            ->setFetchMode(ReviewTable::FETCH_MODE, ReviewTable::FETCH_CLASS);
    }

    public static function delete(IRequest $Request, $reviewID, $accountFingerprint=null) {
        $Delete = self::table()
            ->delete()
            ->where(ReviewTable::COLUMN_ID, $reviewID);

        $accountFingerprint === null ?: $Delete->where(ReviewTable::COLUMN_ACCOUNT_FINGERPRINT, $accountFingerprint);

        if(!$Delete->execute($Request))
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSource(IRequest $Request, $sourceID, $sourceType, $accountFingerprint, $review, $reviewTitle=null, $status=0) {
        $id = strtoupper(uniqid(self::ID_PREFIX));

        $inserted = self::table()->insert(array(
            ReviewTable::COLUMN_ID => $id,
            ReviewTable::COLUMN_SOURCE_ID => $sourceID,
            ReviewTable::COLUMN_SOURCE_TYPE => $sourceType,
            ReviewTable::COLUMN_ACCOUNT_FINGERPRINT => $accountFingerprint,
            ReviewTable::COLUMN_REVIEW_TITLE => $reviewTitle,
            ReviewTable::COLUMN_REVIEW => $review,
            ReviewTable::COLUMN_STATUS => $status,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Review added to song: " . $sourceID, $Request::VERBOSE);

        return $id;
    }

    static function table() {
        return new ReviewTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\ReviewTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}

