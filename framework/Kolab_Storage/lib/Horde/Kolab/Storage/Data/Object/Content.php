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
abstract class Horde_Kolab_Storage_Data_Object_Content
implements Horde_Kolab_Storage_Data_Object_MimeEmbeddable
{
    /**
     * The content mime type.
     *
     * @var string
     */
    protected $_mime_type;

    /**
     * Return the mime type of the object content.
     *
     * @return string The MIME type representing the Kolab content.
     */
    public function getMimeType()
    {
        return $this->_mime_type;
    }

    /**
     * Match the mime type in the provided mime structure map.
     *
     * @param array $map The mime map.
     *
     * @return int The mime part matching the type specific mime type.
     */
    public function matchMimeId(array $map)
    {
        return array_search($this->_mime_type, $map);
    }

    /**
     * Set the type of the object content.
     *
     * @param string $type The Kolab type of the content.
     */
    public function setType($type)
    {
        $this->_mime_type = Horde_Kolab_Storage_Data_Object_MimeType::getMimeTypeFromObjectType($type);
    }

}