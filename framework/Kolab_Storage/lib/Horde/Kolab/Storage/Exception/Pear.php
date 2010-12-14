<?php
/**
 * This class converts PEAR errors into exceptions for the Kolab_Storage
 * package.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * This class converts PEAR errors into exceptions for the Kolab_Storage
 * package.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Exception_Pear
extends Horde_Exception_Pear
{
    /**
     * Exception handling.
     *
     * @param mixed $result The result to be checked for a PEAR_Error.
     *
     * @return mixed Returns the original result if it was no PEAR_Error.
     *
     * @throws Horde_Exception_Pear In case the result was a PEAR_Error.
     */
    static public function catchError($result)
    {
        self::$_class = __CLASS__;
        return parent::catchError($result);
    }
}
