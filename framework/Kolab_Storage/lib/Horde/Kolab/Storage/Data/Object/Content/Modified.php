<?php
/**
 * Generates Kolab content based on an array of object data and the previous content.
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
 * Generates Kolab content based on an array of object data and the previous content.
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
class Horde_Kolab_Storage_Data_Object_Content_Modified
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
     * The contents of the previous Kolab body part.
     *
     * @var resource
     */
    private $_previous;

    /**
     * The content mime type.
     *
     * @var Horde_Kolab_Storage_Data_Object_MimeType
     */
    protected $_mime_type;

    /**
     * Constructor.
     *
     * @param array $object The object data.
     * @param Horde_Kolab_Format $format The Kolab format handler.
     */
    public function __construct(array $object,
                                Horde_Kolab_Format $format)
    {
        $this->_object = $object;
        $this->_format = $format;
    }

    /**
     * Create the Kolab content as a string.
     *
     * @return string The Kolab content.
     */
    public function toString()
    {
        try {
            return $this->_format->save(
                $this->_object, array('previous' => $this->_previous)
            );
        } catch (Horde_Kolab_Format_Exception $e) {
            throw new Horde_Kolab_Storage_Data_Exception(
                'Failed saving Kolab object!', 0, $e
            );
        }
    }

    /**
     * Set the Kolab content of the original message.
     *
     * @param resource $previous The previous content.
     */
    public function setPreviousBody($previous)
    {
        $this->_previous = $previous;
    }
}