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
use Site\Config;
use Site\DB\SiteDB;
use Site\Song\Review\ReviewTag\DB\ReviewTagEntry;
use Site\Song\Review\ReviewTag\DB\ReviewTagTable;

/**
 * Class SongReviewEntry
 * @table song_review
 */
class SongReviewEntry implements IBuildable, IKeyMap
{
    const STATUS_PUBLISHED =            0x000001;

    const STATUS_WRITE_UP =             0x000010;
    const STATUS_CRITIQUE =             0x000020;
    const JOIN_COLUMN_TAGS = 'tags';

    static $StatusOptions = array(
        "Published" =>              self::STATUS_PUBLISHED,

        "Write-Up" =>               self::STATUS_WRITE_UP,
        "Critique" =>               self::STATUS_CRITIQUE,
    );

    /**
	 * @column VARCHAR(64) NOT NULL
     * @index --name index_song_review
     * @unique --name unique_song_review
	 */
	protected $song_id;

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

    public function getSongID() {
		return $this->song_id;
	}

    /**
     * @return mixed
     */
    public function getAccountFingerprint() {
        return $this->account_fingerprint;
    }

    /**
     * @return mixed
     */
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
        ReviewTagEntry::addToSong($Request, $this->getSongID(), $this->getAccountFingerprint(), $tagName, $tagValue);
    }

    public function removeTag($Request, $tagName, $tagValue) {
        ReviewTagEntry::removeFromSong($Request, $this->getSongID(), $this->getAccountFingerprint(), $tagName, $tagValue);
    }

    function update(IRequest $Request, $review=null, $reviewTitle=null, $status=null) {
        $Update = self::table()
            ->update();

        $review === null ?: $Update->update(SongReviewTable::COLUMN_REVIEW, $review);
        $reviewTitle === null ?: $Update->update(SongReviewTable::COLUMN_REVIEW_TITLE, $reviewTitle);
        $status === null ?: $Update->update(SongReviewTable::COLUMN_STATUS, $status);

        $Update->where(SongReviewTable::COLUMN_SONG_ID, $this->getSongID());
        $Update->where(SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT, $this->getAccountFingerprint());

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
        $Request->log("Review updated for song: " . $this->getSongID(), $Request::VERBOSE);
    }

    /**
     * Map data to the key map
     * @param IKeyMapper $Map the map inst to add data to
     * @return void
     */
    function mapKeys(IKeyMapper $Map) {
        $Map->map('song-id', $this->getSongID());
        $Map->map('review-account-fingerprint', $this->getAccountFingerprint());
        $Map->map('review', $this->getReview());
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
     * @param $songID
     * @param int $count
     * @return PDOSelectBuilder
     */
    public static function getLast($songID, $count=null) {
        $count ?: $count = 10;
        return self::query()
            ->where(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_SONG_ID, $songID)
        ->orderBy(SongReviewTable::COLUMN_CREATED, "DESC")
            ->limit($count);
    }

    /**
     * @param $songID
     * @param $accountFingerprint
     * @return SongReviewEntry
     */
    public static function fetch($songID, $accountFingerprint) {
        return self::query()
            ->where(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_SONG_ID, $songID)
            ->where(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT, $accountFingerprint)
            ->fetch();
    }


    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_SONG_ID)
            ->select(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT)
            ->select(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_REVIEW_TITLE)
            ->select(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_REVIEW)
            ->select(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_CREATED)
            ->select(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_STATUS)

            ->select('GROUP_CONCAT(DISTINCT CONCAT(' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_TAG . ', "::", ' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_VALUE . ') SEPARATOR "||")', self::JOIN_COLUMN_TAGS)
            ->leftJoin(ReviewTagTable::TABLE_NAME
                . ' ON ' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_SONG_ID . ' = ' . SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_SONG_ID
                . ' AND ' . ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT . ' = ' . SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT)

            ->groupBy(SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_SONG_ID . ', ' . SongReviewTable::TABLE_NAME . '.' . SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT)

            ->setFetchMode(SongReviewTable::FETCH_MODE, SongReviewTable::FETCH_CLASS);
    }


    static function removeFromSong($Request, $songID, $accountFingerprint) {
        $delete = self::table()
            ->delete()
            ->where(SongReviewTable::COLUMN_SONG_ID, $songID)
            ->where(SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT, $accountFingerprint)
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSong(IRequest $Request, $songID, $accountFingerprint, $review, $reviewTitle=null, $status=0) {
        $inserted = self::table()->insert(array(
            SongReviewTable::COLUMN_SONG_ID => $songID,
            SongReviewTable::COLUMN_ACCOUNT_FINGERPRINT => $accountFingerprint,
            SongReviewTable::COLUMN_REVIEW_TITLE => $reviewTitle,
            SongReviewTable::COLUMN_REVIEW => $review,
            SongReviewTable::COLUMN_STATUS => $status,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Review added to song: " . $songID, $Request::VERBOSE);
    }

    static function table() {
        return new SongReviewTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SongReviewTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}

}

