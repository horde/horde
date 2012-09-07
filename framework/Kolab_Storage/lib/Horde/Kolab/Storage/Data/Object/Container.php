<?php
/**
 * A recipient for Kolab message objects.
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
 * A recipient for Kolab message objects.
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
class Horde_Kolab_Storage_Data_Object_Container
{
    /**
     * The backend driver.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The folder receiving the message.
     *
     * @var string
     */
    private $_folder;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $driver The backend driver.
     * @param string                     $fodler The folder receiving the message.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver,
                                $folder)
    {
        $this->_driver = $driver;
        $this->_folder = $folder;
    }

    /**
     * Store the message.
     *
     * @param Horde_Kolab_Storage_Data_Object_Message $message The message.
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     */
    public function store(Horde_Kolab_Storage_Data_Object_Message $message)
    {
        $headers = $message->createEnvelopeHeaders($this->_driver->getAuth());
        return $this->_driver->appendMessage(
            $this->_folder,
            $message->create()->toString(
                array(
                    'canonical' => true,
                    'stream' => true,
                    'headers' => $headers
                )
            )
        );
    }
}