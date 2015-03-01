<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\DB;
use CPath\Build\IBuildable;
use CPath\Build\IBuildRequest;
use CPath\Data\Map\IKeyMap;
use CPath\Data\Map\IKeyMapper;
use CPath\Data\Schema\PDO\PDOSelectBuilder;
use CPath\Data\Schema\PDO\PDOTableClassWriter;
use CPath\Data\Schema\PDO\PDOTableWriter;
use CPath\Data\Schema\TableSchema;
use CPath\Framework\Data\Serialize\Interfaces\ISerializable;
use CPath\Render\HTML\Element\Form\HTMLForm;
use CPath\Request\IRequest;
use CPath\Request\Validation\Exceptions\ValidationException;
use Site\DB\SiteDB;
use Site\Song\Genre\DB\GenreTable;
use Site\Song\Genre\DB\SongGenreTable;
use Site\Song\System\DB\SongSystemTable;
use Site\Song\System\DB\SystemTable;
use Site\Song\Tag\DB\SongTagEntry;
use Site\Song\Tag\DB\SongTagTable;

/**
 * Class SongEntry
 * @table song
 */
class SongEntry implements IBuildable, IKeyMap, ISerializable
{
	const ID_PREFIX = 'S';

    const JOIN_COLUMN_SYSTEMS = 'systems';
    const JOIN_COLUMN_GENRES = 'genres';
    const JOIN_COLUMN_TAGS = 'tags';

//    const STATUS_NONE =                 0x000000;
    const STATUS_PUBLISHED =            0x000001;

    const STATUS_ORIGINAL =             0x000010;
    const STATUS_REMIX =                0x000020;
    const STATUS_COVER =                0x000040;

    const STATUS_CHIPTUNE =             0x000100;

    const STATUS_LYRICS =               0x001000;
    const STATUS_INSTRUMENTAL =         0x002000;
    const STATUS_SHEET_MUSIC =          0x004000;

    static $StatusOptions = array(
//        "Unpublished" =>            self::STATUS_NONE,
        "Published" =>              self::STATUS_PUBLISHED,

        "Chiptune" =>               self::STATUS_CHIPTUNE,

        "Lyrics" =>                 self::STATUS_LYRICS,
        "Instrumental" =>           self::STATUS_INSTRUMENTAL,
        "Sheet Music" =>            self::STATUS_SHEET_MUSIC,


        "Original" =>               self::STATUS_ORIGINAL,
        "Remix" =>                  self::STATUS_REMIX,
        "Cover" =>                  self::STATUS_COVER,
    );

    
    /**
	 * @column VARCHAR(64) PRIMARY KEY
	 * @select
	 * @search
	 */
	protected $id;

    /**
     * @column VARCHAR(64) NOT NULL
     * @select
     * @insert
     * @search
     */
    protected $title;

    /**
     * TODO: FULLTEXT index
     * @column TEXT
     * @select
     * @insert
     */
    protected $description;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $status;

    /**
     * @column INT
     * @select
     * @insert
     */
    protected $created;

    protected $systems;
    protected $genres;
    protected $tags;

	public function getID() {
		return $this->id;
	}

	public function getCreatedTimestamp() {
		return $this->created;
	}

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
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
        return $statusList ?: array("Unpublished");
    }

    public function getGenreList() {
        if(!$this->genres)
            return array();
        return explode(', ', $this->genres);
    }

    public function getSystemList() {
        if(!$this->systems)
            return array();
        return explode(', ', $this->systems);
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

    public function update(IRequest $Request, $title=null, $description=null) {
        if($this->hasFlags(self::STATUS_PUBLISHED))
            throw new \Exception("Song titles may not be edited once published");

        $Update = self::table()
            ->update();

        $title === null ?: $Update->update(SongTable::COLUMN_TITLE, $title);
        $description === null ?: $Update->update(SongTable::COLUMN_DESCRIPTION, $description);
        $Update->where(SongTable::COLUMN_ID, $this->id);

        if(!$Update->execute($Request))
            throw new \InvalidArgumentException("Could not update " . __CLASS__);
    }

	/**
	 * Map data to the key map
	 * @param IKeyMapper $Map the map inst to add data to
	 * @internal param \CPath\Request\IRequest $Request
	 * @internal param \CPath\Request\IRequest $Request
	 * @return void
	 */
	function mapKeys(IKeyMapper $Map) {
        $Map->map('song-id', $this->getID());
        $Map->map('song-title', $this->getTitle());
        $Map->map('song-created', $this->getCreatedTimestamp());
        $Map->map('song-status', implode(', ', $this->getStatusList()));
        $Map->map('song-description', $this->getDescription());
        $Map->map('song-genres', $this->genres);
        $Map->map('song-systems', $this->systems);
        foreach($this->getTagList() as $name => $value)
            $Map->map('tag-' . $name, $value);
	}

    public function addTag($Request, $tagName, $tagValue) {
        SongTagEntry::addToSong($Request, $this->getID(), $tagName, $tagValue);
    }

    public function removeTag($Request, $tagName, $tagValue) {
        SongTagEntry::removeFromSong($Request, $this->getID(), $tagName, $tagValue);
    }

    public function publish(IRequest $Request, HTMLForm $Form=null) {
        if($this->hasFlags(SongEntry::STATUS_PUBLISHED))
            throw new ValidationException($Form, "Song is already published");

        $tags = $this->getTagList();

        if(!$tags[SongTagEntry::TAG_URL])
            throw new ValidationException($Form, "At least one URL is required to publish");

        if(!$tags[SongTagEntry::TAG_URL_TORRENT] && !$tags[SongTagEntry::TAG_URL_DOWNLOAD])
            throw new ValidationException($Form, "At least one download URL is required to publish (file or torrent yo)");

        $status = $this->status | self::STATUS_PUBLISHED;
        self::table()
            ->update(SongTable::COLUMN_STATUS, $status)
            ->where(SongTable::COLUMN_ID, $this->id)
            ->execute($Request);
    }

        /**
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * String representation of object
	 * @link http://php.net/manual/en/serializable.serialize.php
	 * @return string the string representation of the object or null
	 */
	public function serialize() {
		$values = (array)$this;
		foreach(array_keys($values) as $key)
			if($values[$key] === null)
				unset($values[$key]);
		return json_encode($values);
	}

	// Static

    public static function unserialize($serialized) {
        $Inst = new SongEntry();
        foreach(json_decode($serialized, true) as $name => $value)
            $Inst->$name = $value;
        return $Inst;
    }

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_TITLE)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_DESCRIPTION)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_CREATED)
            ->select(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_STATUS)

            ->select(GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_NAME, self::JOIN_COLUMN_GENRES, 'GROUP_CONCAT(DISTINCT %s SEPARATOR ", ")')
            ->leftJoin(SongGenreTable::TABLE_NAME, SongGenreTable::TABLE_NAME . '.' . SongGenreTable::COLUMN_SONG_ID, SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)
            ->leftJoin(GenreTable::TABLE_NAME, GenreTable::TABLE_NAME . '.' . GenreTable::COLUMN_ID, SongGenreTable::COLUMN_GENRE_ID)

            ->select(SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_NAME, self::JOIN_COLUMN_SYSTEMS, 'GROUP_CONCAT(DISTINCT %s SEPARATOR ", ")')
            ->leftJoin(SongSystemTable::TABLE_NAME, SongSystemTable::TABLE_NAME . '.' . SongSystemTable::COLUMN_SONG_ID, SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)
            ->leftJoin(SystemTable::TABLE_NAME, SystemTable::TABLE_NAME . '.' . SystemTable::COLUMN_ID, SongSystemTable::TABLE_NAME . '.' . SongSystemTable::COLUMN_SYSTEM_ID)

            ->select('GROUP_CONCAT(DISTINCT CONCAT(' . SongTagTable::TABLE_NAME . '.' . SongTagTable::COLUMN_TAG . ', "::", ' . SongTagTable::TABLE_NAME . '.' . SongTagTable::COLUMN_VALUE . ') SEPARATOR "||")', self::JOIN_COLUMN_TAGS)
            ->leftJoin(SongTagTable::TABLE_NAME, SongTagTable::TABLE_NAME . '.' . SongTagTable::COLUMN_SONG_ID, SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)

            ->groupBy(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID)

            ->setFetchMode(SongTable::FETCH_MODE, SongTable::FETCH_CLASS);
    }

    static function create(IRequest $Request, $title, $description) {
        $id = strtoupper(uniqid(self::ID_PREFIX));

        $inserted = self::table()->insert(array(
            SongTable::COLUMN_ID => $id,
            SongTable::COLUMN_TITLE => $title,
            SongTable::COLUMN_DESCRIPTION => $description,
            SongTable::COLUMN_STATUS => 0,
            SongTable::COLUMN_CREATED => time(),
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);

        $Request->log("New Song Entry Inserted: " . $title, $Request::VERBOSE);

        $Song = SongEntry::get($id);

        return $Song;
    }

	static function delete($Request, $id) {
		$delete = self::table()
            ->delete(SongTable::COLUMN_ID, $id)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

	/**
	 * @param $id
	 * @return SongEntry
	 */
	static function get($id) {
		return self::query()
            ->where(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_ID, $id)
            ->fetchOne();
	}

	static function table() {
		return new SongTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\SongTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}


}