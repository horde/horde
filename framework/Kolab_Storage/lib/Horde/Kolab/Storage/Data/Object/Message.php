<?php
/**
 * Represents a MIME message with Kolab content.
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
 * Represents a MIME message with Kolab content.
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
abstract class Horde_Kolab_Storage_Data_Object_Message
{
    /**
     * Generate the headers for the MIME envelope of a Kolab groupware object.
     *
     * @param string $user The current user.
     *
     * @return Horde_Mime_Headers The headers for the MIME envelope.
     */
    abstract public function createEnvelopeHeaders($user);

    /**
     * Convert the message into a string resource that can be appended as a new
     * message to a folder.
     *
     * @param string $user The current user.
     *
     * @return resource The message as string resource.
     */
    abstract public function create();
}