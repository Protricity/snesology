<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Account\Session\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Account\DB\AccountEntry;
use Site\Account\DB\AccountTable;
use Site\Account\Session\DB\SessionEntry;
use Site\Account\Session\DB\SessionTable;

class HTMLSessionsTable extends HTMLPDOQueryTable
{
    public function __construct($fingerprint, $count = null, $short = false) {
        $Query = SessionEntry::query(true)
            ->where(SessionTable::COLUMN_FINGERPRINT, $fingerprint)
            ->orderBy(AccountTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        parent::__construct($Query);
    }
}

