<?php
/**
 * Handles the conversion of Kolab MIME part contents to data arrays.
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
 * Handles the conversion of Kolab MIME part contents to data arrays.
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
abstract class Horde_Kolab_Storage_Object_Writer
{
    /**
     * Convert the object content into a data structure and update the object
     * accordingly.
     *
     * @param resource $content The raw content from the object.
     * @param Horde_Kolab_Storage_Object $object The object that should receive the parsed data.
     */
    abstract public function load($content, Horde_Kolab_Storage_Object $object);

    /**
     * Return the object data in a form that it can be stored in the backend.
     *
     * @param Horde_Kolab_Storage_Object $object The object that should receive the parsed data.
     *
     * @return resource The encoded object data, ready to be written into the
     *                  backend.
     */
    abstract public function save(Horde_Kolab_Storage_Object $object);
}