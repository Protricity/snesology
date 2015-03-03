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
use Site\Song\Tag\DB\SongTagEntry;
use Site\Song\Tag\DB\SongTagTable;

class HTMLSongTagsTable extends HTMLPDOQueryTable
{
    public function __construct($count = null, $tagName=null, $tagValue=null) {
        $Query = SongTagEntry::query()
            ->orderBy(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        $tagName === null ?: $Query->where(SongTagTable::COLUMN_TAG, $tagName);
        $tagValue === null ?: $Query->where(SongTagTable::COLUMN_VALUE, $tagValue . '%', ' LIKE ?');

//        $Query->groupBy(SongTagTable::TABLE_NAME . '.' . SongTagTable::COLUMN_TAG . ',' . SongTagTable::TABLE_NAME . '.' . SongTagTable::COLUMN_VALUE);

        parent::__construct($Query);

        $this->addColumn('song');
        $this->addColumn('tag');
        $this->addSearchColumn(SongTagTable::COLUMN_TAG, 'tag');
        $this->addColumn('tag-value');
        $this->addSearchColumn(SongTagTable::COLUMN_VALUE, 'tag-value');
    }
}

