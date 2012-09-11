<?php
/**
 * Represents a MIME embeddable object.
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
 * Represents a MIME embeddable object.
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
interface Horde_Kolab_Storage_Data_Object_MimeEmbeddable
{
    /**
     * Return the mime type of embeddable object.
     *
     * @return string The MIME type representing the object.
     */
    public function getMimeType();

    /**
     * Return the object as a string that will fill the MIME part body.
     *
     * @return string The body.
     */
    public function toString();
}