<?php
/**
 * Generates the core Kolab content.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Generates the core Kolab content.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
abstract class Horde_Kolab_Storage_Data_Object_Content
{
    /**
     * Return the mime type of the object content.
     *
     * @return string The MIME type representing the Kolab content.
     */
    abstract public function getMimeType();

    /**
     * Return the UID of the embedded Kolab object.
     *
     * @return string The UID of the Kolab content.
     */
    abstract public function getUid();

    /**
     * Create the Kolab content as a string.
     *
     * @return string The Kolab content.
     */
    abstract public function toString();
}