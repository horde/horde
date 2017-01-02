<?php
/**
 * Copyright 2012-2015 Lorenz Schori <lo@znerol.ch>
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv3). If you
 * did not receive this file, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category  Horde
 * @copyright 2012-2015 Lorenz Schori
 * @copyright 2014-2017 Horde LLC
 * @license   http://opensource.org/licenses/gpl-3.0.html GPL-3.0
 * @package   Stringprep
 */

/**
 * Horde wrapper around the znerol/php-stringprep package - a PHP
 * implementation of RFC 3454 - Preparation of Internationalized Strings
 * ("stringprep").
 *
 * @author    Lorenz Schori <lo@znerol.ch>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Lorenz Schori
 * @copyright 2014-2017 Horde LLC
 * @license   http://opensource.org/licenses/gpl-3.0.html GPL-3.0
 * @link      https://github.com/znerol/Stringprep
 * @package   Stringprep
 */
class Horde_Stringprep
{
    /**
     * Ensure that the Stringprep libraries are autoloaded.
     */
    public static function autoload()
    {
        if (file_exists(__DIR__ . '/Stringprep/vendor/autoload.php')) {
            require_once __DIR__ . '/Stringprep/vendor/autoload.php';
        } else {
            require_once __DIR__ . '/../../bundle/vendor/autoload.php';
        }
    }

}
