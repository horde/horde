<?php
/**
 * Maps Kolab types to mime types.
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
 * Maps Kolab types to mime types.
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
class Horde_Kolab_Storage_Data_Object_MimeType_Default
{
    /**
     * Kolab type.
     *
     * @var string
     */
    private $_type;

    /**
     * Constructor.
     *
     * @param string $type The Kolab type.
     */
    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * Return the mime type corresponding to the Kolab type.
     *
     * @return string The mime type.
     */
    public function getMimeType()
    {
        return 'application/x-vnd.kolab.' . $this->_type;
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
        return array_search($this->getMimeType(), $map);
    }
}