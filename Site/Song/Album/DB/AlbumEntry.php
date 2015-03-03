<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 12/19/2014
 * Time: 4:02 PM
 */
namespace Site\Song\Album\DB;
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
use Site\Account\DB\AccountEntry;
use Site\DB\SiteDB;
use Site\Grant\DB\AbstractGrantEntry;
use Site\PGP\Commands\Exceptions\PGPCommandException;
use Site\Song\Tag\DB\TagEntry;

/**
 * Class AlbumEntry
 * @table album
 */
class AlbumEntry extends AbstractGrantEntry implements IBuildable, IKeyMap, ISerializable
{
	const ID_PREFIX = 'SB';

//    const STATUS_NONE =                 0x000000;
    const STATUS_PUBLISHED =            0x000001;

    const STATUS_PRIVATE =              0x000010;

    static $StatusOptions = array(
//        "Unpublished" =>            self::STATUS_NONE,
        "Published" =>              self::STATUS_PUBLISHED,

        "Private" =>                self::STATUS_PRIVATE,
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


    /**
     * @column TEXT
     * @insert
     * @update
     */
    protected $challenge;

    /**
     * @column VARCHAR(64) NOT NULL
     * @insert
     * @update
     */
    protected $answer;

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

    public function getTagList() {
        $tags = explode('||', $this->tags);
        foreach($tags as &$tag)
            $tag = explode('::', $tag);
        return $tags;
    }

    public function hasTag($tagName, $tagValue=null) {
        foreach($this->getTagList() as $tag) {
            list($name, $value) = $tag;
            if($name === $tagName) {
                if($tagValue === null || $tagValue = $value) {
                    return true;
                }
            }
        }
        return false;
    }

    public function update(IRequest $Request, $title=null, $description=null) {
        if($this->hasFlags(self::STATUS_PUBLISHED))
            throw new \Exception("Album titles may not be edited once published");

        $Update = self::table()
            ->update();

        $title === null ?: $Update->update(AlbumTable::COLUMN_TITLE, $title);
        $description === null ?: $Update->update(AlbumTable::COLUMN_DESCRIPTION, $description);
        $Update->where(AlbumTable::COLUMN_ID, $this->id);

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
        $Map->map('album-id', $this->getID(), $this->getTitle());
        $Map->map('album-title', $this->getTitle(), $this->getID());
        $Map->map('album-created', $this->getCreatedTimestamp());
        $Map->map('album-status', implode(', ', $this->getStatusList()));
        $Map->map('album-description', $this->getDescription());
	}


    public function loadChallenge(IRequest $Request) {
        $challenge = $this->table()
            ->select(AlbumTable::COLUMN_CHALLENGE)
            ->where(AlbumTable::COLUMN_ID, $this->id)
            ->fetchColumn(0);

        return $challenge;
    }

    public function loadChallengeAnswer(IRequest $Request) {
        $answer = $this->table()
            ->select(AlbumTable::COLUMN_ANSWER)
            ->where(AlbumTable::COLUMN_ID, $this->id)
            ->fetchColumn(0);

        return $answer;
    }

    protected function updateChallenge(IRequest $Request, $newChallenge, $newAnswer) {
        $this
            ->table()
            ->update(AlbumTable::COLUMN_CHALLENGE, $newChallenge)
            ->update(AlbumTable::COLUMN_ANSWER, $newAnswer)
            ->where(AlbumTable::COLUMN_ID, $this->id)
            ->execute($Request);
    }

    public function addTag($Request, $tagName, $tagValue) {
        TagEntry::addToAlbum($Request, $this->getID(), $tagName, $tagValue);
    }

    public function removeTag($Request, $tagName, $tagValue) {
        TagEntry::removeFromAlbum($Request, $this->getID(), $tagName, $tagValue);
    }

    public function publish(IRequest $Request, HTMLForm $Form=null) {
        if($this->hasFlags(AlbumEntry::STATUS_PUBLISHED))
            throw new ValidationException($Form, "Album is already published");

        if(!$this->hasTag(TagEntry::TAG_URL_ORIGIN))
            throw new ValidationException($Form, "At least one URL is required to publish");

        if(!$this->hasTag(TagEntry::TAG_URL_ORIGIN)
            && !$this->hasTag(TagEntry::TAG_URL_ORIGIN))
            throw new ValidationException($Form, "At least one download URL is required to publish (file or torrent yo)");

        $status = $this->status | self::STATUS_PUBLISHED;
        self::table()
            ->update(AlbumTable::COLUMN_STATUS, $status)
            ->where(AlbumTable::COLUMN_ID, $this->id)
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
        $Inst = new AlbumEntry();
        foreach(json_decode($serialized, true) as $name => $value)
            $Inst->$name = $value;
        return $Inst;
    }

    /**
     * @return PDOSelectBuilder
     */
    static function query() {
        return self::table()
            ->select(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_ID)
            ->select(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_TITLE)
            ->select(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_DESCRIPTION)
            ->select(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_CREATED)
            ->select(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_STATUS)

//            ->select('GROUP_CONCAT(DISTINCT CONCAT(' . AlbumTagTable::TABLE_NAME . '.' . AlbumTagTable::COLUMN_TAG . ', "::", ' . AlbumTagTable::TABLE_NAME . '.' . AlbumTagTable::COLUMN_VALUE . ') SEPARATOR "||")', self::JOIN_COLUMN_TAGS)
//            ->leftJoin(AlbumTagTable::TABLE_NAME, AlbumTagTable::TABLE_NAME . '.' . AlbumTagTable::COLUMN_ALBUM_ID, AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_ID)

            ->groupBy(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_ID)

            ->setFetchMode(AlbumTable::FETCH_MODE, AlbumTable::FETCH_CLASS);
    }
//
//    /**
//     * @param $artist
//     * @return PDOSelectBuilder
//     */
//    static function queryByArtist($artist) {
//        return self::query()
//            ->where(AlbumTagTable::COLUMN_TAG, AlbumTagEntry::TAG_ARTIST)
//            ->where(AlbumTagTable::COLUMN_VALUE, $artist);
//    }
//
//    /**
//     * @param $genre
//     * @return PDOSelectBuilder
//     */
//    static function queryByGenre($genre) {
//        return self::query()
//            ->where(AlbumTagTable::COLUMN_TAG, AlbumTagEntry::TAG_GENRE)
//            ->where(AlbumTagTable::COLUMN_VALUE, $genre);
//    }
//
//    /**
//     * @param $system
//     * @return PDOSelectBuilder
//     */
//    static function queryBySystem($system) {
//        return self::query()
//            ->where(AlbumTagTable::COLUMN_TAG, AlbumTagEntry::TAG_SYSTEM)
//            ->where(AlbumTagTable::COLUMN_VALUE, $system);
//    }

    static function create(IRequest $Request, AccountEntry $Creator, $title, $description) {
        $id = strtoupper(uniqid(self::ID_PREFIX));

        $inserted = self::table()->insert(array(
            AlbumTable::COLUMN_ID => $id,
            AlbumTable::COLUMN_TITLE => $title,
            AlbumTable::COLUMN_DESCRIPTION => $description,
            AlbumTable::COLUMN_STATUS => 0,
            AlbumTable::COLUMN_CREATED => time(),
        ))
            ->execute($Request);

        if(!$inserted)
            throw new \InvalidArgumentException("Could not insert " . __CLASS__);

        $Request->log("New Album Entry Inserted: " . $title, $Request::VERBOSE);

        $Album = AlbumEntry::get($id);

        try {
            $Album->generateChallenge($Request, array($Creator->getFingerprint()));
        } catch (PGPCommandException $ex) {
            if (strpos($ex->getMessage(), 'not found') !== false) {
                $Creator->import($Request);
                $Album->generateChallenge($Request, array($Creator->getFingerprint()));
            } else {
                throw $ex;
            }
        }

        return $Album;
    }

	static function delete($Request, $id) {
		$delete = self::table()
            ->delete(AlbumTable::COLUMN_ID, $id)
			->execute($Request);
		if(!$delete)
			throw new \InvalidArgumentException("Could not delete " . __CLASS__);
	}

	/**
	 * @param $id
	 * @return AlbumEntry
	 */
	static function get($id) {
		return self::query()
            ->where(AlbumTable::TABLE_NAME . '.' . AlbumTable::COLUMN_ID, $id)
            ->fetchOne();
	}

	static function table() {
		return new AlbumTable();
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
		$ClassWriter = new PDOTableClassWriter($DB, __NAMESPACE__ . '\AlbumTable', __CLASS__);
		$Schema->writeSchema($ClassWriter);
		$DBWriter = new PDOTableWriter($DB);
		$Schema->writeSchema($DBWriter);
	}
}