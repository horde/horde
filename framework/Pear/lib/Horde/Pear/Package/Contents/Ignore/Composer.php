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
 * @link      http://pear.horde.org/index.php?package=Pear
 * @package   Pear
 */

/**
 * Ignores elements of Composer packaging/state in created packages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link      http://pear.horde.org/index.php?package=Pear
 * @package   Pear
 */
class Horde_Pear_Package_Contents_Ignore_Composer
implements Horde_Pear_Package_Contents_Ignore
{
    /**
     */
    public function isIgnored(SplFileInfo $element)
    {
        $pathname = $element->getPathname();

        /* Ignore composer state files. */
        if ((strpos($pathname, 'bundle/composer.json') !== false) ||
            (strpos($pathname, 'bundle/composer.lock') !== false)) {
            return true;
        }

        /* Ignore composer generated .git data. */
        if ((strpos($pathname, '/bundle/vendor/') !== false) &&
            (strpos($pathname, '/.git/') !== false)) {
            return true;
        }

        return false;
    }
}
