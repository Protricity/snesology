<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Song\Review\HTML;

use CPath\Data\Map\ISequenceMap;
use CPath\Data\Map\ISequenceMapper;
use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Song\DB\SongEntry;
use Site\Song\DB\SongTable;

class HTMLSongsTable extends HTMLPDOQueryTable
{
    public function __construct($count = null, $short = false) {
        $Query = SongEntry::query()
            ->orderBy(SongTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        parent::__construct($Query);
        $this->addColumn('song-id', 'song');
        $this->addSearchColumn(SongTable::COLUMN_ID, 'song');
        if(!$short) {
            $this->addColumn('song-description', 'description');
        }
        $this->addColumn('song-artist', 'artist');
//        $this->addColumn('song-tags', 'tags');
        if(!$short) {
            $this->addColumn('song-system', 'system');
            $this->addColumn('song-genre', 'genre');
            $this->addColumn('song-status', 'status');

            $this->addSearchColumn(SongTable::COLUMN_STATUS, 'status');
        }

        $this->addColumn('song-created', 'created');
        $this->addSortColumn(SongTable::COLUMN_CREATED, 'created');
    }
}

