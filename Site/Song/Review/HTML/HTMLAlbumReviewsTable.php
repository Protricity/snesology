<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/3/2015
 * Time: 2:42 PM
 */
namespace Site\Song\Review\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use CPath\Request\IRequest;
use Site\Song\Review\DB\ReviewEntry;

class HTMLAlbumReviewsTable extends HTMLPDOQueryTable
{
    public function __construct(IRequest $Request, $songID, $count = null) {
        $Query = ReviewEntry::getLast($songID, $count);
        parent::__construct($Query);
    }
}