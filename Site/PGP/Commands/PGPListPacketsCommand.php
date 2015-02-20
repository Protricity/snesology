<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 11/18/14
 * Time: 8:49 AM
 */
namespace Site\PGP\Commands;

class PGPListPacketsCommand extends AbstractPGPStdInCommand
{
	const ALLOW_STD_ERROR = true;
	const CMD             = "--list-packets";

}