<?php
/**
 * The exception marker for the Kolab_Storage system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * This class provides the standard error class for Kolab Storage exceptions.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Exception extends Horde_Exception_Prior
{
    /**
     * Constants to define the error type.
     */

    /**
     * The specified folder already exists.
     */
    const FOLDER_EXISTS = 10;

    /**
     * The name of the folder has not been specified.
     */
    const FOLDER_NAME_UNSET = 50;

}
