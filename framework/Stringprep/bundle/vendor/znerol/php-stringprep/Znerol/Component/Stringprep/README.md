PHP stringprep
==============

[![Build Status](https://travis-ci.org/znerol/Stringprep.svg?branch=master)](https://travis-ci.org/znerol/Stringprep)

**Please note that this project is not maintained. It was implemented on a
weekend and never used in production by the author. If you intend to use it,
then please fork it, fix it and then sumbit a pull request updating this
document to point to the proper project.**

This is a PHP implementation of RFC 3454 - Preparation of Internationalized
Strings ("stringprep").

See: http://tools.ietf.org/html/rfc3454

Requierements
-------------

* PHP >= 5.3
* PHP intl extension (http://ch1.php.net/manual/en/book.intl.php)
* PHP iconv extension (http://ch1.php.net/manual/en/book.iconv.php)

Example
-------

```php
<?php
require "vendor/autoload.php";

use Znerol\Component\Stringprep\Profile;
use Znerol\Component\Stringprep\ProfileException;

class NameprepExampleProfile extends Profile
{
  /**
   * If set to true the characters from RFC3454 table B.1 are removed from the 
   * output.
   */
  protected $removeZWS = true;

  /**
   * One of CASEFOLD_NONE, CASEFOLD_B2, CASEFOLD_B3.
   */
  protected $casefold = self::CASEFOLD_B_2;

  /**
   * Whether to apply string normalization (NFKC)
   */
  protected $normalize = self::NORM_NFKC;

  /**
   * An array of tables from RFC3454 appendix C.
   */
  protected $prohibit = array(
    self::PROHIBIT_C_1_1,
    self::PROHIBIT_C_1_2,
    self::PROHIBIT_C_2_1,
    self::PROHIBIT_C_2_2,
    self::PROHIBIT_C_3,
    self::PROHIBIT_C_4,
    self::PROHIBIT_C_5,
    self::PROHIBIT_C_6,
    self::PROHIBIT_C_7,
    self::PROHIBIT_C_8,
    self::PROHIBIT_C_9
  );

  /**
   * If set, the mechanism for checking bidirectional strings described in RFC3454 
   * is applied.
   */
  protected $checkbidi = true;
}

$nameprep = new NameprepExampleProfile();

try {
    $result = $nameprep->apply("intérnätional-chars");
    print("+ International characters allowed\n");
}
catch (ProfileException $e) {
    print("! Ooops, international characters should be allowed in this profile\n");
}

try {
    $result = $nameprep->apply("spaces are not allowed");
    print("! Ooops, spaces should be prohibited in this profile\n");
}
catch (ProfileException $e) {
    print("* Spaces prohibited\n");
}
```

License
-------

This software is released under the GNU Lesser General Public License, version 3.0 (LGPL-3.0)

Acknowledgment
--------------

Parts of GNU libidn have been reused in this project:
* http://www.gnu.org/software/libidn/
