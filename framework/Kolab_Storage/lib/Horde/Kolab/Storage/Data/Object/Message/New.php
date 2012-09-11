<?php
/**
 * Represents a new MIME message with new Kolab content.
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
 * Represents a new MIME message with new Kolab content.
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
class Horde_Kolab_Storage_Data_Object_Message_New
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
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Data_Object_Content $content The Kolab content.
     * @param Horde_Kolab_Storage_Driver $driver The backend driver.
     */
    public function __construct(Horde_Kolab_Storage_Data_Object_Addable $content,
                                Horde_Kolab_Storage_Driver $driver)
    {
        $this->_content = $content;
        $this->_driver = $driver;
    }

    /**
     * Generates a new MIME messages that will wrap a Kolab groupware object.
     *
     * @return Horde_Mime_Part The new MIME message.
     */
    private function createEnvelope()
    {
        $envelope = new Horde_Mime_Part();
        $envelope->setName('Kolab Groupware Data');
        $envelope->setType('multipart/mixed');
        $description = new Horde_Mime_Part();
        $description->setName('Kolab Groupware Information');
        $description->setType('text/plain');
        $description->setDisposition('inline');
        $description->setCharset('utf-8');
        $description->setContents(
            Horde_String::wrap(
                sprintf(
                    Horde_Kolab_Storage_Translation::t(
                        "This is a Kolab Groupware object. To view this object you will need an email client that understands the Kolab Groupware format. For a list of such email clients please visit %s"
                    ),
                    'http://www.kolab.org/content/kolab-clients'
                ),
                76,
                "\r\n"
            ),
            array('encoding' => 'quoted-printable')
        );
        $envelope->addPart($description);
        return $envelope;
    }

    /**
     * Generate the headers for the MIME envelope of a Kolab groupware object.
     *
     * @param string $user The current user.
     *
     * @return Horde_Mime_Headers The headers for the MIME envelope.
     */
    private function createEnvelopeHeaders()
    {
        $headers = new Horde_Mime_Headers();
        $headers->setEOL("\r\n");
        $headers->addHeader('From', $this->_driver->getAuth());
        $headers->addHeader('To', $this->_driver->getAuth());
        $headers->addHeader('Date', date('r'));
        $headers->addHeader('Subject', $this->_content->getUid());
        $headers->addHeader('User-Agent', 'Horde::Kolab::Storage v' . Horde_Kolab_Storage::VERSION);
        $headers->addHeader('MIME-Version', '1.0');
        $headers->addHeader('X-Kolab-Type', $this->_content->getMimeType());
        return $headers;
    }

    /**
     * Convert the message into a MIME message that can be appended to a folder.
     *
     * @return Horde_Mime_Part The message as MIME part.
     */
    private function create()
    {
        $envelope = $this->createEnvelope();
        $part = new Horde_Kolab_Storage_Data_Object_Part();
        $envelope->addPart($part->setContents($this->_content));
        $envelope->buildMimeIds();
        return $envelope;
    }

    /**
     * Store the message.
     *
     * @param string $folder The folder receiving the message.
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     */
    public function store($folder)
    {
        $result = $this->_driver->appendMessage(
            $folder,
            $this->create()->toString(
                array(
                    'canonical' => true,
                    'stream' => true,
                    'headers' => $this->createEnvelopeHeaders()
                )
            )
        );
        if (is_object($result) || $result === false || $result === null) {
            throw new Horde_Kolab_Storage_Data_Exception(
                sprintf(
                    'Unexpected return value (%s) when creating an object in folder "%s"!',
                    print_r($result, true), $folder
                )
            );
        }
        return $result;
    }
}