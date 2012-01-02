<?php
/**
 * A modifiable message object.
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
 * A modifiable message object.
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
class Horde_Kolab_Storage_Data_Modifiable
{
    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The backend driver.
     * @param string                     $folder The folder this object belongs to.
     * @param array                      $object The MIME parsed message elements.
     */
    public function __construct($driver, $folder, $object)
    {
        $this->_driver = $driver;
        $this->_folder = $folder;
        $this->_object = $object;
    }

    public function getStructure()
    {
        return $this->_object[1];
    }

    public function setPart($mime_id, $new_part)
    {
        $part = $this->_object[1]->getPart(0);
        if (!empty($part)) {
            $part->setContents('');
        }
        $this->_object[1]->alterPart($mime_id, $new_part);
        $this->_object[1]->buildMimeIds();
    }

    public function store()
    {
        $result = $this->_driver->appendMessage(
            $this->_folder,
            $this->_object[1]->toString(
                array(
                    'canonical' => true,
                    'stream' => true,
                    'headers' => $this->_object[0]
                )
            )
        );
        if (is_object($result) || $result === false || $result === null) {
            throw new Horde_Kolab_Storage_Exception(
                'Unexpected return value when modifying an object!'
            );
        }
        return $result;
    }
}