<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Song\Review\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Song\DB\SongEntry;
use Site\Song\DB\SongTable;

class HTMLSongsTable extends HTMLPDOQueryTable
{
    public function __construct($count = null) {
        $Query = SongEntry::query()
            ->orderBy(SongTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        parent::__construct($Query);
        $this->addColumn('song-id');
        $this->addColumn('song-title', 'title');
        $this->addColumn('song-description', 'description');
        $this->addColumn('song-artist', 'artist');
        $this->addColumn('song-systems', 'systems');
        $this->addColumn('song-status', 'status');
        $this->addColumn('song-genres', 'genres');
        $this->addColumn('song-created', 'created');

        $this->addSearchColumn(SongTable::COLUMN_ID, 'song-id');
        $this->addSearchColumn(SongTable::COLUMN_TITLE, 'title');
        $this->addSearchColumn(SongTable::COLUMN_STATUS, 'status');
        $this->addSearchColumn(SongTable::COLUMN_CREATED, 'created');
    }
}