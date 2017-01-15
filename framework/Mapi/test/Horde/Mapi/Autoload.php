<?php
/**
 * Setup autoloading for the tests.
 *
 * @category   Horde
 * @package    Mapi
 * @subpackage UnitTests
 * @author     Jan Schneider <jan@horde.org>
 * @license    http://www.horde.org/licenses/lgpl21 LGPL 2.1
 */

/* Load Math_BigInteger now, before composer autoloaders can mess with the
 * include path. This is to avoid using phpseclib's Math\BigInteger from
 * Horde_Pgp instead of PEAR's Math_BigInteger. */
require_once 'Math/BigInteger.php';