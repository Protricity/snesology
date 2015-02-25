<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 2/24/2015
 * Time: 2:24 PM
 */
namespace Site\Song\Review\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use CPath\Request\IRequest;
use Site\Song\Review\DB\SongReviewEntry;

class HTMLSongReviewsTable extends HTMLPDOQueryTable
{
    public function __construct(IRequest $Request, $songID, $count=null) {
        $Query = SongReviewEntry::getLast($songID, $count);
        parent::__construct($Query);
    }
}

