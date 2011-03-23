<?php
/**
 * Horde_Pear_Package_Contents_Include_All:: includes all files.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */

/**
 * Horde_Pear_Package_Contents_Include_All:: includes all files.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Pear
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Pear
 */
class Horde_Pear_Package_Contents_Include_All
implements Horde_Pear_Package_Contents_Include
{
    /**
     * Tell whether to include the element.
     *
     * @param SplFileInfo $element The element to check.
     *
     * @return bool True if the element should be included, false otherwise.
     */
    public function isIncluded(SplFileInfo $element)
    {
        return true;
    }
}