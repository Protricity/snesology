<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/2/2015
 * Time: 1:06 AM
 */
namespace Site\Song\Genre\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Song\Genre\DB\GenreEntry;
use Site\Song\Genre\DB\GenreTable;

class HTMLGenresTable extends HTMLPDOQueryTable
{
    public function __construct($count = null) {
        $Query = GenreEntry::query()
            ->orderBy(GenreTable::COLUMN_NAME)
            ->limit($count ?: 25);

        parent::__construct($Query);
        $this->addColumn('genre', 'genre');
        $this->addColumn('description', 'description');
//        $this->addColumn('status', 'status');
//        $this->addColumn('created', 'created');

        $this->addSearchColumn(GenreTable::COLUMN_NAME, 'genre');
//        $this->addSearchColumn(GenreTable::COLUMN_STATUS, 'status');
//        $this->addSearchColumn(GenreTable::COLUMN_CREATED, 'created');
    }
}