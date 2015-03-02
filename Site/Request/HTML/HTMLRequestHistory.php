<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 2/24/2015
 * Time: 2:51 PM
 */
namespace Site\Request\HTML;

use CPath\Render\Helpers\RenderIndents as RI;
use CPath\Render\HTML\Attribute\IAttributes;
use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use CPath\Render\HTML\Header\IHeaderWriter;
use CPath\Render\HTML\Header\IHTMLSupportHeaders;
use CPath\Render\HTML\IRenderHTML;
use CPath\Request\IRequest;
use Site\Account\DB\AccountEntry;
use Site\Request\DB\RequestEntry;
use Site\Request\DB\RequestTable;
use Site\Song\Review\DB\SongReviewEntry;

class HTMLRequestHistory extends HTMLPDOQueryTable
{
    public function __construct($path = null) {
        $Query = RequestEntry::query();
        if($path)
            $Query->where(RequestTable::COLUMN_PATH, $path);
        parent::__construct($Query);

        $this->addColumn('path');
        $this->addSearchColumn(RequestTable::COLUMN_PATH, 'path');
        $this->addColumn('account');
        $this->addColumn('created');
        $this->addSortColumn(RequestTable::COLUMN_CREATED, 'created');
    }
}