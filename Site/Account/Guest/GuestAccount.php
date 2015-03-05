<?php
/**
 * Created by PhpStorm.
 * User: ari
 * Date: 3/5/2015
 * Time: 10:44 AM
 */
namespace Site\Account\Guest;

use CPath\Request\IRequest;
use Site\Account\DB\AccountEntry;

class GuestAccount
{
    const PGP_NAME = 'guest';
    const PGP_EMAIL = 'guest@snesology.com';
    const PGP_PASSPHRASE = '0B8FE7460D';
    const PGP_FINGERPRINT = '3569F4891719CB69AECFEE2C76A53BD62EBA3335';
    const PGP_PUBLIC_KEY = '-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v2

mQENBFT4pnoBCAC9tCf8iX0xQiq6Xos8TkuN+7W9N8OurykCiFG+UQaq8QK1OlfX
FOauySf0JVfqy4mPwa6qgx7UrVz8m/7JBUeTdEPkyoC9mcHHjNbqxahU+NBX4wan
loU2WT0GC8ssISF/rfEkoGwbdfLruQlny+jXtRurqUdDLd7qaLKKjWhJlQqN+kU3
AycRE6pXBbqDjPXTbhebVsHxzLPNbPparfP//sy7744K2VcIUM27LnBe2Ui8pNXR
yLFjGub4sWyloNdk1o8WdK7ofHUcWJ6UBBttyjkIfrTxlgNtNweQ7+tzvf8Hw/V6
pGOMJAb72BIhpB/7YyaAVRV9yLZufeF39W2lABEBAAG0G2d1ZXN0IDxndWVzdEBz
bmVzb2xvZ3kuY29tPokBOQQTAQIAIwUCVPimegIbDwcLCQgHAwIBBhUIAgkKCwQW
AgMBAh4BAheAAAoJEHalO9YuujM1SAoH/24LLCOO33njcugMImerTAFbwr+JooUq
051txaAN52cyQ7KO9ud2ZNrSWSPony2F2fZ9DOHIb6BUiAdzqcqquXz7XZKnmQSm
4gUaXYZEjGwpcvMi5XtJM6s4F3+EInLgBtMJhd32pTpjTyJ8aQwgMBBNSfbsAoPr
xTbbdf0Sp7y9bbYf3bNqNx91kJOyGVLC0QtrXTGfVepSA/BdEVWqiFrLEmf1GtMH
PRSVWu3vpIhNdGvpLDP4UM8t50Ph4kjQMKHhAPksrmK+PAl2wgxHez7W1GSWgQwg
C0ILIS3BKP8wWnN4Xd5SMML5/rnBD0epHVRBqQnlS1fzgPpaSyjJjQo=
=AY+V
-----END PGP PUBLIC KEY BLOCK-----';
    const PGP_PRIVATE_KEY = '-----BEGIN PGP PRIVATE KEY BLOCK-----
Version: GnuPG v2

lQO+BFT4pnoBCAC9tCf8iX0xQiq6Xos8TkuN+7W9N8OurykCiFG+UQaq8QK1OlfX
FOauySf0JVfqy4mPwa6qgx7UrVz8m/7JBUeTdEPkyoC9mcHHjNbqxahU+NBX4wan
loU2WT0GC8ssISF/rfEkoGwbdfLruQlny+jXtRurqUdDLd7qaLKKjWhJlQqN+kU3
AycRE6pXBbqDjPXTbhebVsHxzLPNbPparfP//sy7744K2VcIUM27LnBe2Ui8pNXR
yLFjGub4sWyloNdk1o8WdK7ofHUcWJ6UBBttyjkIfrTxlgNtNweQ7+tzvf8Hw/V6
pGOMJAb72BIhpB/7YyaAVRV9yLZufeF39W2lABEBAAH+AwMCPaklLVCWpBK6koEV
x2cV+EKp8++eobn9XyIL9p+BxmZHrJER4pgnDXWq52EDCpkYKNbVxF65vcq7MJ7B
qt7b278ynmO8dgVzlZNJAHQqNs6VOjHHfqU+P/yptLxxoK4FGcH86abucg4C34TF
RJYHeh9Wo0Ew7WF3qI2pZaegNqxQkoiObnUXb1u0GppHXNedTcd1qJaetsdhPEOF
QG5pHQ29hh30Z9BkS1Ke9WD866QTYZhkpHEtrs3g9tmTcIZI4ZRqVTCI4iU7q928
9s8XdrvJAU/tnhxiLl4zEjFOlIoC/Kzkyp4GfDSt6io1IsUBnXvabYbQYxYLsP+u
epUXcqah0IuoTyMGaH4mVC0px0SoGyNr5zk6yOES9QPVJ2PQ8biResH95lWHRb1b
VHkPjAQtYY8xIJbj+ZtiFHg+UMiOb3vR/gaA1QLpiEodGCVKIWEGZJ/V7P27VzPS
vA8ZXTHWTRreThw7GcEtAWBdm7+t9N79MpvgWIZPZoqv26i7YIWKHIsCI+uTi1g6
XWFErFRlkQINwQrc86ydNX6/kYTGkKWwI40RPmJIgxfiZeQZrZyLeOzR8CbjDmor
2jqEe2MAUsxUSfyhNtkizH4VQw6uq2pXWcHT3FtjjvjARiMUgqUII+0l4zl4bF65
+oMvHoY0B7X2umUlRXc271SfPdKK+PiVpGpJ4cOm/Uxxt1IG47ueOcEbY+UsH/Eh
YRDpmaVALyU08F334ZAC0T2i1+wBersMtJ6L8X2hfAaLQd+AY2huNSIVGIJODGhu
onBEvs8jh1ErUyiY5FWN/4uNr+sQH/m3uTc9dC9vtbDYJRcGe7wQnAgQgkm1YLLV
g91pDKA2C7prXwI3CanQiGN8eHnTDEHsoEfGAT3FX7MQwNp+lnJSGjnLL5FRSgYd
gLQbZ3Vlc3QgPGd1ZXN0QHNuZXNvbG9neS5jb20+iQE5BBMBAgAjBQJU+KZ6AhsP
BwsJCAcDAgEGFQgCCQoLBBYCAwECHgECF4AACgkQdqU71i66MzVICgf/bgssI47f
eeNy6AwiZ6tMAVvCv4mihSrTnW3FoA3nZzJDso7253Zk2tJZI+ifLYXZ9n0M4chv
oFSIB3Opyqq5fPtdkqeZBKbiBRpdhkSMbCly8yLle0kzqzgXf4QicuAG0wmF3fal
OmNPInxpDCAwEE1J9uwCg+vFNtt1/RKnvL1tth/ds2o3H3WQk7IZUsLRC2tdMZ9V
6lID8F0RVaqIWssSZ/Ua0wc9FJVa7e+kiE10a+ksM/hQzy3nQ+HiSNAwoeEA+Syu
Yr48CXbCDEd7PtbUZJaBDCALQgshLcEo/zBac3hd3lIwwvn+ucEPR6kdVEGpCeVL
V/OA+lpLKMmNCg==
=mhjQ
-----END PGP PRIVATE KEY BLOCK-----';

    // Static

    static function getOrCreate(IRequest $Request) {
        static $Account = null;
        if($Account)
            return $Account;

        $Account = AccountEntry::query()
            ->where(AccountTable::COLUMN_FINGERPRINT, self::PGP_FINGERPRINT)
            ->fetch();
        if($Account)
            return $Account;

        $Account = AccountEntry::create($Request, self::PGP_PUBLIC_KEY);
        return $Account;
    }
}