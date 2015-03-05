<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Song\Genre\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Song\DB\SongEntry;
use Site\Song\DB\SongTable;

class HTMLGenreSongsTable extends HTMLPDOQueryTable
{
    public function __construct($genre, $count = null) {
        $Query = SongEntry::queryByGenre($genre)
            ->orderBy(SongTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        parent::__construct($Query);
        $this->addColumn('song-id');
        $this->addColumn('song-title', 'title');
        $this->addColumn('description', 'description');
        $this->addColumn('artist', 'artist');
        $this->addColumn('system', 'system');
        $this->addColumn('genre', 'genre');
        $this->addColumn('status', 'status');
        $this->addColumn('created', 'created');

        $this->addSearchColumn(SongTable::COLUMN_ID, 'song-id');
        $this->addSearchColumn(SongTable::COLUMN_TITLE, 'title');
        $this->addSearchColumn(SongTable::COLUMN_STATUS, 'status');
        $this->addSearchColumn(SongTable::COLUMN_CREATED, 'created');
    }
}