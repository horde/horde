<?php
/**
 * Represents an old MIME message that receives updated Kolab content.
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
 * Represents an old MIME message that receives updated Kolab content.
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
class Horde_Kolab_Storage_Data_Object_Message_Modified
{
    /**
     * The message content.
     *
     * @var Horde_Kolab_Storage_Data_Object_Content
     */
    private $_content;

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
     * The object ID of the previous object.
     *
     * @var string
     */
    private $_obid;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data_Object_Content $content The Kolab content.
     * @param Horde_Kolab_Storage_Driver $driver The backend driver.
     * @param string $folder The folder receiving the message.
     * @param string $obid The object ID of the previous object.
     */
    public function __construct(Horde_Kolab_Storage_Data_Object_Content_Modified $content,
                                Horde_Kolab_Storage_Driver $driver,
                                $folder,
                                $obid)
    {
        $this->_content = $content;
        $this->_driver = $driver;
        $this->_folder = $folder;
        $this->_obid = $obid;
    }

    /**
     * Store the message.
     *
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     */
    public function store()
    {
        list($headers, $body) = $this->_driver->fetchComplete(
            $this->_folder, $this->_obid
        );
        $mime_id = $this->_content->matchMimeId($body->contentTypeMap());
        if ($mime_id === false) {
            throw new Horde_Kolab_Storage_Data_Exception(
                sprintf(
                    'Missing expected mime type (%s) in object "%s" in folder "%s"!',
                    $this->_content->getMimeType(),
                    $this->_obid,
                    $this->_folder
                )
            );
        }
        $original = $body->getPart($mime_id);
        $original->setContents(
            $this->_driver->fetchBodypart($this->_folder, $this->_obid, $mime_id)
        );
        $this->_content->setPreviousBody($original->getContents(array('stream' => true)));

        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $body->alterPart($mime_id, $part->setContents($this->_content));
        $body->buildMimeIds();

        $result = $this->_driver->appendMessage(
            $this->_folder,
            $body->toString(
                array(
                    'canonical' => true,
                    'stream' => true,
                    'headers' => $headers
                )
            )
        );
        if (is_object($result) || $result === false || $result === null) {
            throw new Horde_Kolab_Storage_Data_Exception(
                sprintf(
                    'Unexpected return value (%s) when creating an object in folder "%s"!',
                    print_r($result, true), $this->_folder
                )
            );
        }
        $this->_driver->deleteMessages($this->_folder, array($this->_obid));
        $this->_driver->expunge($this->_folder);
        return $result;
    }
}