<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Song\Tag\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Forum\Tag\DB\ThreadTagEntry;
use Site\Forum\Tag\DB\ThreadTagTable;
use Site\Song\DB\SongTable;
use Site\Song\Tag\DB\TagEntry;
use Site\Song\Tag\DB\TagTable;

class HTMLThreadTagsTable extends HTMLPDOQueryTable
{
    public function __construct($threadID, $count = null, $tagName=null, $tagValue=null) {
        $Query = ThreadTagEntry::query()
            ->orderBy(SongTable::TABLE_NAME . '.' . SongTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        $Query->where(ThreadTagTable::COLUMN_THREAD_ID, $threadID);
        $tagName === null ?: $Query->where(ThreadTagTable::COLUMN_TAG, $tagName);
        $tagValue === null ?: $Query->where(ThreadTagTable::COLUMN_VALUE, $tagValue . '%', ' LIKE ?');

//        $Query->groupBy(TagTable::TABLE_NAME . '.' . TagTable::COLUMN_TAG . ',' . TagTable::TABLE_NAME . '.' . TagTable::COLUMN_VALUE);

        parent::__construct($Query);
//
//        $this->addColumn('song');
//        $this->addColumn('tag');
//        $this->addSearchColumn(TagTable::COLUMN_TAG, 'tag');
//        $this->addColumn('tag-value');
//        $this->addSearchColumn(TagTable::COLUMN_VALUE, 'tag-value');
    }
}

