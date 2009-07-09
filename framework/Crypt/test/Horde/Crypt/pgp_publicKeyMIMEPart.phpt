--TEST--
Horde_Crypt_pgp::publicKeyMIMEPart().
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

class PrefsStub {
    function getValue($pref)
    {
        if ($pref == 'sending_charset') {
            return 'iso-8859-1';
        }
        die('unknown preference');
    }
}

require_once 'Horde/Nls.php';
require 'pgp.inc';

$prefs = new PrefsStub;
$mime_part = $pgp->publicKeyMIMEPart($pubkey);
echo $mime_part->getType() . "\n\n";
echo $mime_part->getContents();

?>
--EXPECTF--
application/pgp-keys

-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v%d.%d.%d (GNU/Linux)

mQGiBETcWvARBADNitbvsWy5/hhV+WcU2ttmtXkAj2DqJVgJdGS2RH8msO0roG5j
CQK/e0iMJki5lfdgxvxxWgStYMnfF5ftgWA7JV+BZUzJt12Lobm0zdENv2TqL2vc
xlPTmEGsvfPDTbY+Gr3jvuODboXat7bUn2E723WXPdh2A7KNNnLou7JF2wCgiKs/
RqNKM/Zm01PxLbQ+rs9ghd0D/jLUfJeYWySoDsvfO8e4UyDxDVTBLkkdw3XzLx1F
4SS/Cc2Z9yJuXiepzSH/G/vhdN5ROv12kJwA4FbwsFv5C1uCQleWiPngFixca9Nw
lAd2X2Cp0/4D2XRq1M9dEbcYdrgAuyzt2ZToj3aFaYNGwjfHoLqSngOu6/d3KD1d
i0b2A/9wnXo41kPwS73gU1Un2KKMkKqnczCQHdpopO6NjKaLhNcouRauLrgbrS5y
A1CW+nxjkKVvWrP/VFBmapUpjE1C51J9P0/ub8tRr7H0xHdTQyufv01lmfkjUpVF
n3GVf95l4seBFzD7r580aTx+dJztoHEGWrsWZTNJwo6IIlFOIbQlTXkgTmFtZSAo
TXkgQ29tbWVudCkgPG1lQGV4YW1wbGUuY29tPohgBBMRAgAgBQJE3FrwAhsjBgsJ
CAcDAgQVAggDBBYCAwECHgECF4AACgkQfKdEJrreq9fivACeLBcWErSQU4ZGQsje
dhwfdst9cLQAnRETpwmqt/XvcGFVsOE28MUrUzOGuQENBETcWvAQBADNgIJ4nDlf
gBOI/iqyNy08C9+MjxrqMylrj4TPn3rRk8wySr2myX+j9CML5EHOtsxANYeI9u7h
OF11n5Z8fDht/WGJrNR7EtRhNN6mkKsPaEuO3fhz6EgwJYReUVzDJbvnV2fRCvQo
EGaSntZGQaQzIzIL+/gMEFpEVRK1P2I3VwADBQP+K2Rmmkm3DonXFlUUDWWdhEF4
b7fy5/IPj41PSSOdo0IP4dprFoe15Vs9gWOYvzcnjy+BbOwhVwsjE3F36hf04od3
uTSM0dLS/xmpSvgbBh181T5c3W5aKT/daVhyxXJ4csxE+JCVKbaBubne0DPEuyZj
rYlL5Lm0z3VhNCcR0LyISQQYEQIACQUCRNxa8AIbDAAKCRB8p0Qmut6r16Y3AJ9h
umO5uT5yDcir3zwqUAxzBAkE4ACcCtGfb6usaTKnNXo+ZuLoHiOwIE4=
=GCjU
-----END PGP PUBLIC KEY BLOCK-----
