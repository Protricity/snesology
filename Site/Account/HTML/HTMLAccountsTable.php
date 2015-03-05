<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/1/2015
 * Time: 7:02 PM
 */
namespace Site\Account\HTML;

use CPath\Render\HTML\Element\Table\HTMLPDOQueryTable;
use Site\Account\DB\AccountEntry;
use Site\Account\DB\AccountTable;

class HTMLAccountsTable extends HTMLPDOQueryTable
{
    public function __construct($count = null, $short = false) {
        $Query = AccountEntry::query(true)
            ->orderBy(AccountTable::COLUMN_CREATED, "DESC")
            ->limit($count ?: 25);

        parent::__construct($Query);
        $this->addColumn('fingerprint');
        $this->addColumn('inviter');
        $this->addColumn('name');
        $this->addColumn('email');
        $this->addColumn('created');
        $this->addColumn('public-key');
        $this->addSearchColumn(AccountTable::COLUMN_FINGERPRINT, 'fingerprint');
        $this->addSearchColumn(AccountTable::COLUMN_EMAIL, 'email');
        $this->addSearchColumn(AccountTable::COLUMN_NAME, 'name');
    }
}

