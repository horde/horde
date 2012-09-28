<?php
/**
 * Converts between Kolab MIME parts and data arrays.
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
 * Converts between Kolab MIME parts and data arrays.
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
class Horde_Kolab_Storage_Object_Writer_Format extends Horde_Kolab_Storage_Object_Writer
{
    /**
     * The format parser factory.
     *
     * @var Horde_Kolab_Format_Factory
     */
    private $_factory;

    /**
     * Additional parameters for the format parsers.
     *
     * @var array
     */
    private $_params;

    /**
     * Holds a parser instance per object type.
     *
     * @var array
     */
    private $_formats;

    /**
     * Condtructor.
     *
     * @param Horde_Kolab_Format_Factory $factory The parser factory.
     * @param array $params Additional parameters for the format parsers.
     */
    public function __construct(Horde_Kolab_Format_Factory $factory, $params = array())
    {
        $this->_factory = $factory;
        $this->_params = $params;
    }

    /**
     * Convert the object content into a data structure and update the object
     * accordingly.
     *
     * @param resource $content The raw content from the object.
     * @param Horde_Kolab_Storage_Object $object The object that should receive the parsed data.
     */
    public function load($content, Horde_Kolab_Storage_Object $object)
    {
        try {
            $object->setData($this->_getParser($object->getType())->load($content));
            return true;
        } catch (Horde_Kolab_Format_Exception $e) {
            $object->setContent($content);
            return $e;
        }
    }

    /**
     * Return the object data in a form that it can be stored in the backend.
     *
     * @param Horde_Kolab_Storage_Object $object The object that should receive the parsed data.
     *
     * @return resource The encoded object data, ready to be written into the
     *                  backend.
     */
    public function save(Horde_Kolab_Storage_Object $object)
    {
        try {
            return $this->_getParser($object->getType())->save($object->getData(), array('previous' => $object->getCurrentContent()));
        } catch (Horde_Kolab_Format_Exception $e) {
            throw new Horde_Kolab_Storage_Object_Exception(
                sprintf('Failed writing the Kolab object: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    private function _getParser($type)
    {
        return $this->_factory->create('Xml', $type, $this->_params);
    }
}