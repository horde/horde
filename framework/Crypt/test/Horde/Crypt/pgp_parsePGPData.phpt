--TEST--
Horde_Crypt_pgp::parsePGPData().
--SKIPIF--
<?php require 'pgp_skipif.inc'; ?>
--FILE--
<?php

require 'pgp.inc';

var_dump($pgp->parsePGPData(file_get_contents(dirname(__FILE__) . '/fixtures/pgp_signed.txt')));

?>
--EXPECT--
array(3) {
  [0]=>
  array(2) {
    ["type"]=>
    int(2)
    ["data"]=>
    array(17) {
      [0]=>
      string(34) "-----BEGIN PGP SIGNED MESSAGE-----"
      [1]=>
      string(10) "Hash: SHA1"
      [2]=>
      string(0) ""
      [3]=>
      string(40) "0123456789012345678901234567890123456789"
      [4]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [5]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [6]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [7]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [8]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [9]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [10]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [11]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [12]=>
      string(89) "The quick brown fox jumps over the lazy dog. The quick brown fox jumps over the lazy dog."
      [13]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [14]=>
      string(44) "The quick brown fox jumps over the lazy dog."
      [15]=>
      string(40) "0123456789012345678901234567890123456789"
      [16]=>
      string(33) "!"$§%&()=?^´°`+#-.,*'_:;<>|~\{[]}"
    }
  }
  [1]=>
  array(2) {
    ["type"]=>
    int(5)
    ["data"]=>
    array(7) {
      [0]=>
      string(29) "-----BEGIN PGP SIGNATURE-----"
      [1]=>
      string(33) "Version: GnuPG v1.4.5 (GNU/Linux)"
      [2]=>
      string(0) ""
      [3]=>
      string(64) "iD8DBQFE3JNgfKdEJrreq9cRAm4lAJ48IbiwbO4ToXa2BrJaAZAFt43AiACZATs+"
      [4]=>
      string(24) "gnfrwrK41BzMWmVRhtjB1Po="
      [5]=>
      string(5) "=5HXb"
      [6]=>
      string(27) "-----END PGP SIGNATURE-----"
    }
  }
  [2]=>
  array(2) {
    ["type"]=>
    int(6)
    ["data"]=>
    array(1) {
      [0]=>
      string(0) ""
    }
  }
}
