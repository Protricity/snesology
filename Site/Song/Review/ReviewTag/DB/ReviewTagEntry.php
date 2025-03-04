<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\Review\ReviewTag\DB;
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
use CPath\Request\Exceptions\RequestException;
use CPath\Request\IRequest;
use Site\Account\DB\AccountTable;
use Site\DB\SiteDB;
use Site\Song\Review\DB\ReviewTable;

/**
 * Class ReviewTagEntry
 * @table song_review_tag
 */
class ReviewTagEntry implements IBuildable, IKeyMap, IRenderHTML
{
    const TAG_TYPE_DEFAULT = 's';
    const TAG_TYPE_STRING = 's';
    const TAG_TYPE_BOOLEAN = 'b';
    const TAG_TYPE_5STAR = '5s';

    const TAG_RECOMMENDED = 'b:recommended';
    const TAG_RATING = '5s:rating';

    const JOIN_ACCOUNT_NAME = 'account_name';

    static $TagDefaults = array(
        "Recommended" => self::TAG_RECOMMENDED,
        "5 Star Rating" => self::TAG_RATING,
    );

    public function __construct($tag=null, $value=null) {
        $tag === null ?: $this->tag = $tag;
        $value === null ?: $this->value = $value;
    }

    /**
     * @column VARCHAR(64) NOT NULL
     * @index --name index_review_tag
     */
    protected $review_id;


    /**
     * @column VARCHAR(64) NOT NULL
     * @index --name index_review_tag
     */
    protected $account_fingerprint;

    /**
     * @column VARCHAR(64) NOT NULL
     * @index --name index_review_tag_value
     */
    protected $tag;

    /**
     * @column VARCHAR(256) NOT NULL
     * @index --name index_review_tag_value
     */
    protected $value;

    protected $account_name;

	public function getReviewID() {
		return $this->review_id;
	}

    public function getAccountFingerprint() {
        return $this->account_fingerprint;
    }

    public function getAccountName() {
        return $this->account_name;
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
        $Map->map('review-tag-' . $this->getTagName(), $this->getTagValue());
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

    static function removeFromReview($Request, $reviewID, $accountFingerprint, $tag, $tagValue='%') {
        $delete = self::table()
            ->delete()
            ->where(ReviewTagTable::COLUMN_REVIEW_ID, $reviewID)
            ->where(ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT, $accountFingerprint)
            ->where(ReviewTagTable::COLUMN_TAG, $tag)
            ->where(ReviewTagTable::COLUMN_VALUE, $tagValue, " LIKE ?")
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToReview(IRequest $Request, $reviewID, $accountFingerprint, $tagName, $tagValue) {
        $tagValue = ReviewTagEntry::sanitize($tagName, $tagValue);
        $inserted = self::table()->insert(array(
            ReviewTagTable::COLUMN_REVIEW_ID => $reviewID,
            ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT=> $accountFingerprint,
            ReviewTagTable::COLUMN_TAG => $tagName,
            ReviewTagTable::COLUMN_VALUE => $tagValue,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Review Tag added to song: " . $tagName, $Request::VERBOSE);
    }

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_TAG)
            ->select(ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_VALUE)
            ->select(ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT)
            ->select(ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_REVIEW_ID)

            ->select(AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_NAME, self::JOIN_ACCOUNT_NAME)
            ->leftJoin(AccountTable::TABLE_NAME, AccountTable::TABLE_NAME . '.' . AccountTable::COLUMN_FINGERPRINT, ReviewTagTable::TABLE_NAME . '.' . ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT)

            ->groupBy(ReviewTable::TABLE_NAME . '.' . ReviewTable::COLUMN_ID)

            ->setFetchMode(ReviewTable::FETCH_MODE, ReviewTable::FETCH_CLASS);
    }

    static function table() {
        return new ReviewTagTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\ReviewTagTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}


    static function sanitize($tagName, $tagValue) {
        $tagType = null;
        if(strpos($tagName, ':') !== false)
            list($tagType) = explode(':', $tagName);

        switch($tagType) {
            default:
            case self::TAG_TYPE_STRING:
                $tagValue = preg_replace('/[^a-zA-Z0-9_ -]/', ' ', $tagValue);
                break;
            case self::TAG_TYPE_BOOLEAN:
                $tagValue = $tagValue === true || strcasecmp($tagValue, 'true') === 0 || strcasecmp($tagValue, 'yes') === 0 || $tagValue == 1;
                break;
            case self::TAG_TYPE_5STAR:
                $tagValue = floatval($tagValue);
                if($tagValue < 0 || $tagValue > 5)
                    throw new RequestException("Invalid 5 star rating");
                $tagValue = sprintf('%.1f', $tagValue);
                break;
        }

        return $tagValue;
    }
}