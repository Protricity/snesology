<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Song\Tag\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Song\DB\SongTable;
use Site\Song\Tag\DB\TagEntry;
use Site\Song\Tag\DB\TagTable;

class HTMLTagsTable extends HTMLPDOQueryTable
{
    public function __construct($count = null, $tagName=null, $tagValue=null) {
        $Query = TagEntry::query()
            ->orderBy(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        $tagName === null ?: $Query->where(TagTable::COLUMN_TAG, $tagName);
        $tagValue === null ?: $Query->where(TagTable::COLUMN_VALUE, $tagValue . '%', ' LIKE ?');

//        $Query->groupBy(TagTable::TABLE_NAME . '.' . TagTable::COLUMN_TAG . ',' . TagTable::TABLE_NAME . '.' . TagTable::COLUMN_VALUE);

        parent::__construct($Query);

        $this->addColumn('song');
        $this->addColumn('tag');
        $this->addSearchColumn(TagTable::COLUMN_TAG, 'tag');
        $this->addColumn('tag-value');
        $this->addSearchColumn(TagTable::COLUMN_VALUE, 'tag-value');
    }
}

