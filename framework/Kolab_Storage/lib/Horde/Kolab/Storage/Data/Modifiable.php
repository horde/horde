<?php
/**
 * A modifiable message object.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A modifiable message object.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
        $this->_object[1]->getPart(0)->setContents('');
        $this->_object[1]->alterPart($mime_id, $new_part);
        $this->_object[1]->buildMimeIds();
    }

    public function store()
    {
        return $this->_driver->appendMessage(
            $this->_folder,
            $this->_object[1]->toString(
                array(
                    'canonical' => true,
                    'stream' => true,
                    'headers' => $this->_object[0]
                )
            )
        );
    }
}