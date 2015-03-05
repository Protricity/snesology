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
use Site\Song\Review\DB\ReviewEntry;

class HTMLSongReviewsTable extends HTMLPDOQueryTable
{
    public function __construct(IRequest $Request, $songID, $count=null) {
        $Query = ReviewEntry::getLast($songID, $count);
        parent::__construct($Query);

//        $this->addColumn('id');
//        $this->addColumn('source-id');
//        $this->addColumn('review-fingerprint');
//        $this->addColumn('review');
//        $this->addColumn('review-fingerprint');
    }
}

