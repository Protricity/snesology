<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\Tag\DB;
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
 * Class SongTagEntry
 * @table song_tag
 */
class SongTagEntry implements IBuildable, IKeyMap, IRenderHTML
{
    const TAG_TYPE_DEFAULT = 's';
    const TAG_TYPE_STRING = 's';
    const TAG_TYPE_BOOLEAN = 'b';
    const TAG_TYPE_NUMERIC = 'n';

    const TAG_URL = 'url';
    const TAG_URL_TORRENT = 'url-torrent';
    const TAG_URL_DOWNLOAD = 'url-download';
    const TAG_URL_ICON = 'url-icon';
    const TAG_URL_COVER_FRONT = 'url-cover-front';
    const TAG_URL_COVER_BACK = 'url-cover-back';

    const TAG_ENTRY_ACCOUNT = 'entry-account';

    const TAG_LYRICIST = 'lyricist';
    const TAG_COMPOSER = 'composer';
    const TAG_CONDUCTOR = 'conductor';
    const TAG_LOCATION = 'location';
    const TAG_LEAD_ARTIST = 'lead-artist';
    const TAG_ENCODING = 'encoding';
    const TAG_BITRATE = 'bitrate';

    const TAG_RELEASE_YEAR = 'release-year';

    const TAG_ORIGINAL_SONG = 'original-song';

    const TAG_DURATION = 'duration';
    const TAG_LANGUAGE = 'language';
    const TAG_PUBLISHER = 'publisher';

    const TAG_TRACK_NUMBER = 'track-number';

    static $TagDefaults = array(
        "Publish URL" => self::TAG_URL,
        "Torrent Magnet Link" => self::TAG_URL_TORRENT,
        "Download URL" => self::TAG_URL_DOWNLOAD,
        "Icon URL" => self::TAG_URL_ICON,
        "Album Cover URL (Front)" => self::TAG_URL_COVER_FRONT,
        "Album Cover URL (Back)" => self::TAG_URL_COVER_BACK,

        "Entered By" => self::TAG_ENTRY_ACCOUNT,

        "Lyricist" => self::TAG_LYRICIST,
        "Composer" => self::TAG_COMPOSER,
        "Conductor" => self::TAG_CONDUCTOR,
        "Location" => self::TAG_LOCATION,
        "Lead Artist" => self::TAG_LEAD_ARTIST,
        "Encoding" => self::TAG_ENCODING,
        "Bitrate" => self::TAG_BITRATE,

        "Release Year" => self::TAG_RELEASE_YEAR,
        "Original Song" => self::TAG_ORIGINAL_SONG,

        "Duration" => self::TAG_DURATION,
        "Language" => self::TAG_LANGUAGE,
        "Publisher" => self::TAG_PUBLISHER,

        "Track Number" => self::TAG_TRACK_NUMBER,
    );

    /**
	 * @column VARCHAR(64) NOT NULL
     * @index --name index_song_tag
	 */
	protected $song_id;

    /**
     * @column VARCHAR(64) NOT NULL
     * @index --name index_song_tag
     * @index --name index_tag_value
     */
    protected $tag;

    /**
     * @column VARCHAR(256) NOT NULL
     * @index --name index_tag_value
     */
    protected $value;

	public function getSongID() {
		return $this->song_id;
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
        $Map->map('tag-' . $this->getTagName(), $this->getTagValue());
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



    static function removeFromSong($Request, $songID, $tag, $tagValue) {
        $delete = self::table()
            ->delete()
            ->where(SongTagTable::COLUMN_SONG_ID, $songID)
            ->where(SongTagTable::COLUMN_TAG, $tag)
            ->where(SongTagTable::COLUMN_VALUE, $tagValue)
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSong(IRequest $Request, $songID, $tag, $tagValue) {
        $inserted = self::table()->insert(array(
            SongTagTable::COLUMN_SONG_ID => $songID,
            SongTagTable::COLUMN_TAG => $tag,
            SongTagTable::COLUMN_VALUE => $tagValue,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Tag added to song: " . $tag, $Request::VERBOSE);
    }

    static function table() {
        return new SongTagTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SongTagTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}