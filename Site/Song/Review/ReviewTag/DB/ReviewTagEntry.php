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
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use Site\DB\SiteDB;

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

    static $TagDefaults = array(
        "Recommended" => self::TAG_RECOMMENDED,
        "5 Star Rating" => self::TAG_RATING,
    );

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

	public function getReviewID() {
		return $this->review_id;
	}

    public function getAccountFingerprint() {
        return $this->account_fingerprint;
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

    static function removeFromSong($Request, $songID, $accountFingerprint, $tag, $tagValue) {
        $delete = self::table()
            ->delete()
            ->where(ReviewTagTable::COLUMN_SONG_ID, $songID)
            ->where(ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT, $accountFingerprint)
            ->where(ReviewTagTable::COLUMN_TAG, $tag)
            ->where(ReviewTagTable::COLUMN_VALUE, $tagValue)
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSong(IRequest $Request, $songID, $accountFingerprint, $tag, $tagValue) {
        $inserted = self::table()->insert(array(
            ReviewTagTable::COLUMN_SONG_ID => $songID,
            ReviewTagTable::COLUMN_TAG => $tag,
            ReviewTagTable::COLUMN_ACCOUNT_FINGERPRINT=> $accountFingerprint,
            ReviewTagTable::COLUMN_VALUE => $tagValue,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Review Tag added to song: " . $tag, $Request::VERBOSE);
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
}