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
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use Site\DB\SiteDB;
use Site\Song\Album\DB\AlbumTable;
use Site\Song\DB\SongTable;

/**
 * Class TagEntry
 * @table tag
 */
class TagEntry implements IBuildable, IKeyMap, IRenderHTML
{
    const TYPE_DEFAULT = 's';
    const TYPE_STRING = 's';
    const TYPE_BOOLEAN = 'b';
    const TYPE_NUMERIC = 'n';
    const TYPE_URL = 'url';

    const TAG_URL_ORIGIN = 'url:origin';
    const TAG_URL_TORRENT = 'url:torrent';
    const TAG_URL_DOWNLOAD = 'url:download';
    const TAG_URL_ICON = 'url:icon';
    const TAG_URL_COVER_FRONT = 'url:cover-front';
    const TAG_URL_COVER_BACK = 'url:cover-back';

    const TAG_ENTRY_ACCOUNT = 'entry-account';

    const TAG_ARTIST = 'artist';
    const TAG_GENRE = 'genre';
    const TAG_SYSTEM = 'system';

    const TAG_ORIGINAL = 'original';

    const TAG_CHIP_STYLE = 'chip-style';

    const TAG_LYRICIST = 'lyricist';
    const TAG_COMPOSER = 'composer';
    const TAG_CONDUCTOR = 'conductor';
    const TAG_LOCATION = 'location';
    const TAG_LEAD_ARTIST = 'lead-artist';
    const TAG_ENCODING = 'encoding';
    const TAG_BITRATE = 'bitrate';

    const TAG_RELEASE_YEAR = 'release-year';


    const TAG_DURATION = 'duration';
    const TAG_LANGUAGE = 'language';
    const TAG_PUBLISHER = 'publisher';

    const TAG_TRACK_NUMBER = 'track-number';
    const TAG_SIMILAR = 'similar';
    const JOIN_COLUMN_SONGS = 'songs';

    static $TagDefaults = array(
        "Origin URL" => self::TAG_URL_ORIGIN,
        "Torrent Magnet Link" => self::TAG_URL_TORRENT,
        "Download URL" => self::TAG_URL_DOWNLOAD,
        "Icon URL" => self::TAG_URL_ICON,
        "Album Cover URL (Front)" => self::TAG_URL_COVER_FRONT,
        "Album Cover URL (Back)" => self::TAG_URL_COVER_BACK,

        "Artist" => self::TAG_ARTIST,
        "Genre" => self::TAG_GENRE,
        "System" => self::TAG_SYSTEM,

        "Original Song" => self::TAG_ORIGINAL,
        "Similar Song" => self::TAG_SIMILAR,

        "Chip Style" => self::TAG_CHIP_STYLE,

        "Entered By" => self::TAG_ENTRY_ACCOUNT,

        "Lyricist" => self::TAG_LYRICIST,
        "Composer" => self::TAG_COMPOSER,
        "Conductor" => self::TAG_CONDUCTOR,
        "Location" => self::TAG_LOCATION,
        "Lead Artist" => self::TAG_LEAD_ARTIST,
        "Encoding" => self::TAG_ENCODING,
        "Bitrate" => self::TAG_BITRATE,

        "Release Year" => self::TAG_RELEASE_YEAR,

        "Duration" => self::TAG_DURATION,
        "Language" => self::TAG_LANGUAGE,
        "Publisher" => self::TAG_PUBLISHER,

        "Track Number" => self::TAG_TRACK_NUMBER,
    );

    /**
	 * @column VARCHAR(64) NOT NULL
     * @index --name index_song_tag
	 */
	protected $source_id;

    /**
     * @column ENUM('song', 'album') NOT NULL
     * @index --name index_tag_source
     */
    protected $source_type;

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

    protected $songs;
    protected $albums;

    public function getSourceID() {
        return $this->source_id;
    }

    public function getSongList() {
        $songs = array();
        foreach(explode('||', $this->songs) as $song) {
            list($songID, $songTitle) = explode('::', $song);
            if($songID)
                $songs[$songID] = $songTitle;
        }
        return $songs;
    }

    public function getAlbumList() {
        $albums = array();
        foreach(explode('||', $this->albums) as $album) {
            list($songTitle, $albumTitle) = explode('::', $album);
            if($songTitle)
                $albums[$songTitle] = $albumTitle;
        }
        return $albums;
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
        foreach($this->getSongList() as $songID => $songTitle)
            $Map->map('song', $songID, $songTitle);
        foreach($this->getAlbumList() as $albumID => $albumTitle)
            $Map->map('album', $albumID, $albumTitle);
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
            ->select(TagTable::TABLE_NAME . '.' . TagTable::COLUMN_TAG)
            ->select(TagTable::TABLE_NAME . '.' . TagTable::COLUMN_VALUE)
//            ->select(TagTable::TABLE_NAME . '.' . TagTable::COLUMN_SONG_ID, TagTable::COLUMN_SONG_ID, "GROUP_CONCAT(%s SEPARATOR ', ')")

//            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_TITLE, 'song_title', "GROUP_CONCAT(%s SEPARATOR ', ')")
            ->select('GROUP_CONCAT(DISTINCT CONCAT(' . TagTable::TABLE_NAME . '.' . TagTable::COLUMN_SOURCE_ID . ', "::", ' . SongTable::TABLE_NAME . '.' . SongTable::COLUMN_TITLE. ') SEPARATOR "||")', self::JOIN_COLUMN_SONGS)

            ->leftJoin(SongTable::TABLE_NAME,
                TagTable::TABLE_NAME . '.' . TagTable::COLUMN_SOURCE_ID . ' = ' . SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID
                . ' AND ' . TagTable::TABLE_NAME . '.' . TagTable::COLUMN_SOURCE_TYPE . ' = "song"'
            )

            ->leftJoin(AlbumTable::TABLE_NAME,
                TagTable::TABLE_NAME . '.' . TagTable::COLUMN_SOURCE_ID . ' = ' . AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_ID
                . ' AND ' . TagTable::TABLE_NAME . '.' . TagTable::COLUMN_SOURCE_TYPE . ' = "album"'
            )

            ->groupBy(TagTable::TABLE_NAME . '.' . TagTable::COLUMN_TAG . ',' . TagTable::TABLE_NAME . '.' . TagTable::COLUMN_VALUE)

            ->setFetchMode(TagTable::FETCH_MODE, TagTable::FETCH_CLASS);
    }

    /**
     * @return PDOSelectBuilder
     */
    static function queryTags() {
        return self::query()
            ->where(TagTable::COLUMN_SOURCE_TYPE, 'song');
    }

    /**
     * @return PDOSelectBuilder
     */
    static function queryAlbumTags() {
        return self::query()
            ->where(TagTable::COLUMN_SOURCE_TYPE, 'album');
    }

    static function removeFromSong($Request, $songID, $tag, $tagValue) {
        $delete = self::table()
            ->delete()
            ->where(TagTable::COLUMN_SOURCE_TYPE, 'song')
            ->where(TagTable::COLUMN_SOURCE_ID, $songID)
            ->where(TagTable::COLUMN_TAG, $tag)
            ->where(TagTable::COLUMN_VALUE, $tagValue)
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function removeFromAlbum($Request, $albumID, $tag, $tagValue) {
        $delete = self::table()
            ->delete()
            ->where(TagTable::COLUMN_SOURCE_TYPE, 'album')
            ->where(TagTable::COLUMN_SOURCE_ID, $albumID)
            ->where(TagTable::COLUMN_TAG, $tag)
            ->where(TagTable::COLUMN_VALUE, $tagValue)
            ->execute($Request);

        if(!$delete)
            throw new \InvalidArgumentException("Could not delete " . __CLASS__);
    }

    static function addToSong(IRequest $Request, $songID, $tag, $tagValue) {
        $inserted = self::table()->insert(array(
            TagTable::COLUMN_SOURCE_ID => $songID,
            TagTable::COLUMN_SOURCE_TYPE => 'song',
            TagTable::COLUMN_TAG => $tag,
            TagTable::COLUMN_VALUE => $tagValue,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Tag added to song: " . $tag . '=' . $tagValue, $Request::VERBOSE);
    }

    static function addToAlbum(IRequest $Request, $albumID, $tag, $tagValue) {
        $inserted = self::table()->insert(array(
            TagTable::COLUMN_SOURCE_ID => $albumID,
            TagTable::COLUMN_SOURCE_TYPE => 'album',
            TagTable::COLUMN_TAG => $tag,
            TagTable::COLUMN_VALUE => $tagValue,
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);
        $Request->log("Tag added to song: " . $tag . '=' . $tagValue, $Request::VERBOSE);
    }

    static function table() {
        return new TagTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\TagTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}