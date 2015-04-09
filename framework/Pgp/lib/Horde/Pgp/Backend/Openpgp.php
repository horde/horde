<?php
/**
 * Copyright 2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Pgp
 */

/**
 * PGP backend that uses the openpgp-php library.
 *
 * NOTE: This class is NOT intended to be accessed outside of this package.
 * There is NO guarantees that the API of this class will not change across
 * versions.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @internal
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      https://github.com/singpolyma/openpgp-php/
 * @package   Pgp
 */
class Horde_Pgp_Backend_Openpgp
extends Horde_Pgp_Backend
{
    /**
     * Autoload necessary libraries.
     */
    static public function autoload()
    {
        /* Ensure the openpgp-php libraries are autoloaded. */
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } else {
            require_once __DIR__ . '/../../../../bundle/vendor/autoload.php';
        }
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        self::autoload();
    }

}
