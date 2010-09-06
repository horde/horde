<?php
/**
 * Horde_Element_Constants:: provides the constants for this package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */

/**
 * Horde_Element_Constants:: provides the constants for this package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Element
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Element
 */
class Horde_Element_Constants
{
    const DATA_DIR = '@data_dir@';

    static public function getDataDirectory()
    {
        if (strpos(self::DATA_DIR, '@data_dir') === 0) {
            return join(
                DIRECTORY_SEPARATOR,
                array(dirname(__FILE__), '..', '..', '..', 'data')
            );
        }
        return self::DATA_DIR . DIRECTORY_SEPARATOR . 'Element';
    }
}
