<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/3/2015
 * Time: 4:04 PM
 */
namespace Site\Song\Album\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Song\Album\DB\AlbumEntry;
use Site\Song\Album\DB\AlbumTable;

class HTMLAlbumsTable extends HTMLPDOQueryTable
{
    public function __construct($count = null, $short = false) {
        $Query = AlbumEntry::query()
            ->orderBy(AlbumTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        parent::__construct($Query);
        $this->addColumn('album-id', 'album');
        $this->addSearchColumn(AlbumTable::COLUMN_ID, 'album');
        if (!$short) {
            $this->addColumn('album-description', 'description');
        }
        $this->addColumn('album-artist', 'artist');
//        $this->addColumn('album-tags', 'tags');
        if (!$short) {
            $this->addColumn('album-system', 'system');
            $this->addColumn('album-genre', 'genre');
            $this->addColumn('album-status', 'status');

            $this->addSearchColumn(AlbumTable::COLUMN_STATUS, 'status');
        }

        $this->addColumn('album-created', 'created');
        $this->addSortColumn(AlbumTable::COLUMN_CREATED, 'created');
    }
}