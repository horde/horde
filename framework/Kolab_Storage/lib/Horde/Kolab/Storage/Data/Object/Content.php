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
class Horde_Kolab_Storage_Data_Object_Content
{
    /**
     * Kolab format handler.
     *
     * @var Horde_Kolab_Format
     */
    private $_format;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Format $format The Kolab format handler.
     */
    public function __construct(Horde_Kolab_Format $format)
    {
        $this->_format = $format;
    }

    /**
     * Create the Kolab content as a string.
     *
     * @param array $object The object data.
     *
     * @return string The Kolab content.
     */
    public function create(array $object)
    {
        try {
            return $this->_format->save($object);
        } catch (Horde_Kolab_Format_Exception $e) {
            throw new Horde_Kolab_Storage_Data_Exception(
                'Failed saving Kolab object!', 0, $e
            );
        }
    }

    /**
     * Modify a previous Kolab object.
     *
     * @param array  $object   The new object data.
     * @param string $previous The previous data.
     *
     * @return string The new Kolab content.
     */
    public function modify(array $object, $previous)
    {
        try {
            return $this->_format->save(
                $object, array('previous' => $previous)
            );
        } catch (Horde_Kolab_Format_Exception $e) {
            throw new Horde_Kolab_Storage_Data_Exception(
                'Failed saving Kolab object!', 0, $e
            );
        }
    }
}