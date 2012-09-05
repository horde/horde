<?php
/**
 * Basic functionality for the Kolab content.
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
 * Basic functionality for the Kolab content.
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
abstract class Horde_Kolab_Storage_Data_Object_Content_Base
extends Horde_Kolab_Storage_Data_Object_Content
{
    /**
     * The content mime type.
     *
     * @var Horde_Kolab_Storage_Data_Object_MimeType
     */
    private $_mime_type;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data_Object_MimeType $mime_type The content mime type.
     */
    public function __construct(Horde_Kolab_Storage_Data_Object_MimeType $mime_type)
    {
        $this->_mime_type = $mime_type;
    }

    /**
     * Return the mime type of the object content.
     *
     * @return string The MIME type representing the Kolab content.
     */
    public function getMimeType()
    {
        return $this->_mime_type->getMimeType();
    }
}