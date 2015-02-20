<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/18/14
 * Time: 9:42 AM
 */
namespace Site\PGP\Commands;

class PGPExportPrivateKeyCommand extends PGPExportPublicKeyCommand
{
	const ALLOW_STD_ERROR = false;
	const CMD             = "--export-secret-keys %s";

	public function __construct($fingerprint, $armored=true) {
		parent::__construct($fingerprint, $armored);
	}


}