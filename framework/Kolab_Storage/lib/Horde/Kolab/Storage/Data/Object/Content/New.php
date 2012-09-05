<?php
/**
 * Generates fresh Kolab content based on an array of object data.
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
 * Generates fresh Kolab content based on an array of object data.
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
class Horde_Kolab_Storage_Data_Object_Content_New
extends Horde_Kolab_Storage_Data_Object_Content_Base
{
    /**
     * Object data.
     *
     * @var array
     */
    private $_object;

    /**
     * Kolab format handler.
     *
     * @var Horde_Kolab_Format
     */
    private $_format;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data_Object_MimeType $mime_type The content mime type.
     * @param array              $object The object data.
     * @param Horde_Kolab_Format $format The Kolab format handler.
     */
    public function __construct(Horde_Kolab_Storage_Data_Object_MimeType $mime_type,
                                array $object,
                                Horde_Kolab_Format $format)
    {
        parent::__construct($mime_type);
        $this->_format = $format;
        $this->_object = $object;
    }

    /**
     * Create the Kolab content as a string.
     *
     * @return string The Kolab content.
     */
    public function toString()
    {
        try {
            return $this->_format->save($this->_object);
        } catch (Horde_Kolab_Format_Exception $e) {
            throw new Horde_Kolab_Storage_Data_Exception(
                'Failed saving Kolab object!', 0, $e
            );
        }
    }
}