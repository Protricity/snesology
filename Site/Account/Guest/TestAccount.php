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
use Site\Account\DB\AccountTable;

class TestAccount
{
    const PGP_NAME = 'test';
    const PGP_EMAIL = 'test@snesology.com';
    const PGP_PASSPHRASE = '7460D0B8FE';
    const PGP_FINGERPRINT = '5EC31D62F3B65582FA3B47DA409F9C3995943F72';
    const PGP_PUBLIC_KEY = '-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v2

mQENBFT4sewBCADPfAeY2l80lj8KZWxmF7dEgHDXzQv5RFfqJG3LBV5tzBU5+xWu
A7ajwVFJ9w57yLcwUs+Dl3GRU4joIxY4zJl6s3NZD0JGo2g8ikk0OX1I1vFlPrIu
6b/jUG+1dGYmDgc1bueE6+d6vnAiHzHeT/lWz9kN/z9xh5GTyMUTEEKHwbOquY9C
RxsRyMNnIRTzOkfJnNQ9juLY1j3fxCy507dTIHPn1p+sAl/+fibh1VJe/VwXOJON
6FdLUS1KeLC9q03vcQ3zVNIkBAtqapVkSvxxGw9da3l3bkbNDxPyzuTo1cAqerlP
HeFz1oA6/erM+1Rk3ufPRPfhQuKsxQ7okkNXABEBAAG0HnRlc3RlciA8dGVzdGVy
QHNvbmdwb3J0YWwuY29tPokBOQQTAQIAIwUCVPix7AIbDwcLCQgHAwIBBhUIAgkK
CwQWAgMBAh4BAheAAAoJEECfnDmVlD9yb/gH/jp7qvMK2CgYK3hK5oBDvPYALjV0
Yv1wLcw4/Fpeo/R6NAutdUNjUp5SIMho+i/rXLjrcKkIvrfm2j9X2d2QGzWufnT4
86huUU2uWncJT3UY6rK2bMPxNrF9ERt8N3eeK6B58s8KD44kPztf4SXKx3VdEr32
skRn+jkUZz4vcJtYiPS/sBCfNmOFH+pXK9QpEgUqW8HrrKlJ8bNwaLqC+AFtXJs6
38q3oDK2ZmDvocWxrmb0uZ69fkOP7ZUiErxcME5WkkMp1Gi/4SMqB1czU+XeG4K8
HyXIIg+kIAu3XjMqRgPFaQMhypJVvA7NP9koql5QhjYID2t7FQlYRK48IVI=
=Ipr7
-----END PGP PUBLIC KEY BLOCK-----';
    const PGP_PRIVATE_KEY = '-----BEGIN PGP PRIVATE KEY BLOCK-----
Version: GnuPG v2

lQO+BFT4sewBCADPfAeY2l80lj8KZWxmF7dEgHDXzQv5RFfqJG3LBV5tzBU5+xWu
A7ajwVFJ9w57yLcwUs+Dl3GRU4joIxY4zJl6s3NZD0JGo2g8ikk0OX1I1vFlPrIu
6b/jUG+1dGYmDgc1bueE6+d6vnAiHzHeT/lWz9kN/z9xh5GTyMUTEEKHwbOquY9C
RxsRyMNnIRTzOkfJnNQ9juLY1j3fxCy507dTIHPn1p+sAl/+fibh1VJe/VwXOJON
6FdLUS1KeLC9q03vcQ3zVNIkBAtqapVkSvxxGw9da3l3bkbNDxPyzuTo1cAqerlP
HeFz1oA6/erM+1Rk3ufPRPfhQuKsxQ7okkNXABEBAAH+AwMC+R7KbHUoUOq6LEsI
hNlL3OLsIxRgNRWT6GgmADrQ8MkOozYelFCz2p7WtRk2hr320WFB72+9w6YM6bTH
q8dJkX95b1sBc9i1pvZS4s7tJYDO2h25c5vM8apBkNPwOQQ6oRqOaf4ViQ+cXJT+
EoWS6/plUPHsfWhFyaAx7uwSsaIKBZTn/pMPIiKB0Kzm821Kqddl1kh4wPSp07Fg
xJ4bAUTOUashpf73W748M4TWnHVkTj0PYg1vyEHQeb8R40qw19zVQ7apDSKKdhfM
3a4o0sYOpbg5YUCD7SkKXQFJ5UPzCD5CfEa2TQRLBN1KMgE5SHQIN9fM/BE49iH2
pJpC0crqKs7W3T/MuDgZsTlE8bmpuqYF5UVphZfakNlS4cfiZkVBt1yl54DFX1wF
BB0vCfLWDVI+1R8pCukCwLveYx59n+8eOaMpiY4ty7yTuAHXuWSo3ARUr+bcJUax
HOI0DMYgDvwM8d6js5P5O4WP41pqzy6ejS/PXVMYrSBwWp7Qeb0vgWkL0JSSkbDr
EKAlLOF+zM+V3BA2LgqPijiZBQ5ObHUhcDVYllCsIx2RxMBDX1qUlGHJkBrFmXRT
s3FA1L5vwBSiBf32aRNoPSMSiRKovM3TJuBUYwFO9Di+cWnAkyxJFVcakf5zYRWu
LhviugoCVoRxIdUvtWelCYkORupCfRpjUWITqSZuFLv7NQ70PPg+Qy3EHxOfiJUb
oKoxzJ3U7mnpBHCaZItj2SpYHsUIh3drg8m5dbVFu9AMdi81/hacHRtalcho/UaI
EWjCtSJxfJwlTgrYwty6+EjJFuBkhiAkWjcG8AUkB1wLs+0Lu+aaXIcgwKtArt91
dcgtAclPY8/AGqsbpax5Wpa0YTfjhkuRLxZSkmaB/CinU+e/hJ/xnwE3n1UPKVDA
IrQedGVzdGVyIDx0ZXN0ZXJAc29uZ3BvcnRhbC5jb20+iQE5BBMBAgAjBQJU+LHs
AhsPBwsJCAcDAgEGFQgCCQoLBBYCAwECHgECF4AACgkQQJ+cOZWUP3Jv+Af+Onuq
8wrYKBgreErmgEO89gAuNXRi/XAtzDj8Wl6j9Ho0C611Q2NSnlIgyGj6L+tcuOtw
qQi+t+baP1fZ3ZAbNa5+dPjzqG5RTa5adwlPdRjqsrZsw/E2sX0RG3w3d54roHny
zwoPjiQ/O1/hJcrHdV0SvfayRGf6ORRnPi9wm1iI9L+wEJ82Y4Uf6lcr1CkSBSpb
weusqUnxs3BouoL4AW1cmzrfyregMrZmYO+hxbGuZvS5nr1+Q4/tlSISvFwwTlaS
QynUaL/hIyoHVzNT5d4bgrwfJcgiD6QgC7deMypGA8VpAyHKklW8Ds0/2SiqXlCG
NggPa3sVCVhErjwhUg==
=pLcA
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