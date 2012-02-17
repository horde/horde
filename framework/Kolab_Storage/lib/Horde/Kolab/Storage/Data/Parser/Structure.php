<?php
/**
 * Parses an object by relying on the MIME capabilities of the backend.
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
 * Parses an object by relying on the MIME capabilities of the backend.
er.
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
class Horde_Kolab_Storage_Data_Parser_Structure
implements  Horde_Kolab_Storage_Data_Parser
{
    /**
     * The backend driver.
     *
     * @param Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * The bridge between the backend object and the format parser.
     *
     * @param Horde_Kolab_Storage_Data_Format
     */
    private $_format;

    /**
     * A log handler.
     *
     * @param mixed
     */
    private $_logger;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Storage_Driver $driver The backend driver.
     */
    public function __construct(Horde_Kolab_Storage_Driver $driver)
    {
        $this->_driver = $driver;
    }

    /**
     * Set the logger.
     *
     * @param mixed $logger The log handler (must provide the warn() method).
     *
     * @return NULL
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Indicate a problem in the log.
     *
     * @param Exception $message The warn message.
     */
    private function _warn($message)
    {
        if ($this->_logger === null) {
            throw $message;
        } else if ($this->_logger === false) {
            return;
        }
        $this->_logger->warn($message->getMessage());
    }

    /**
     * Set the format handler.
     *
     * @param Horde_Kolab_Storage_Data_Format $format The data object <-> format
     *                                                bridge.
     *
     * @return NULL
     */
    public function setFormat(Horde_Kolab_Storage_Data_Format $format)
    {
        $this->_format = $format;
    }

    /**
     * Return the format handler.
     *
     * @return Horde_Kolab_Storage_Data_Format The data object <-> format
     *                                         bridge.
     */
    public function getFormat()
    {
        if ($this->_format === null) {
            throw new Horde_Kolab_Storage_Exception(
                'The format handler has been left undefined!'
            );
        }
        return $this->_format;
    }

    /**
     * Fetches the objects for the specified backend IDs.
     *
     * @param string $folder  The folder to access.
     * @param array  $obids   The object backend IDs to fetch.
     * @param array  $options Additional options for fetching.
     * <pre>
     *  'type'    - Required argument specifying the object type that should be
     *              parsed.
     *  'version' - Optional argument specifying the version of the object
     *              format.
     * </pre>
     *
     * @return array The objects.
     */
    public function fetch($folder, $obids, $options = array())
    {
        $objects = array();
        $this->_completeOptions($options);
        $structures = $this->_driver->fetchStructure($folder, $obids);
        foreach ($structures as $obid => $structure) {
            if (!isset($structure['structure'])) {
                throw new Horde_Kolab_Storage_Exception(
                    'Backend returned a structure without the expected "structure" element.'
                );
            }
            try {
                $objects[$obid] = $this->getFormat()->parse($folder, $obid, $structure['structure'], $options);
            } catch (Horde_Kolab_Storage_Exception $e) {
                $objects[$obid] = false;
                $this->_warn($e);
            }
            if ($this->_driver->hasCatenateSupport()) {
                $objects[$obid]['__structure'] = $structure['structure'];
            }
            $this->_fetchAttachments($objects[$obid], $folder, $obid, $options);
        }
        return $objects;
    }

    /**
     * Completes the given object with any required attachments.
     *
     * @param array  $object  The object to fetch attachments for.
     * @param string $folder  The folder to access.
     * @param array  $obid    The object backend ID.
     * @param array  $options Additional options for fetching.
     *
     * @return NULL
     */
    private function _fetchAttachments(&$object, $folder, $obid, $options = array())
    {
        //@todo: implement
    }

    /**
     * Fetch the specified mime part.
     *
     * @param string $folder  The folder to access.
     * @param string $obid    The backend ID to parse from.
     * @param string $mime_id The ID of the part that should be fetched.
     *
     * @return resource A stream for the specified body part.
     */
    public function fetchId($folder, $obid, $mime_id)
    {
        return $this->_driver->fetchBodypart($folder, $obid, $mime_id);
    }

    /**
     * Complete the options.
     *
     * @param array  $options Options.
     * <pre>
     *  'type'    - Required argument specifying the object type.
     *  'version' - Optional argument specifying the version of the object
     *              format.
     * </pre>
     *
     * @return NULL
     */
    private function _completeOptions(&$options)
    {
        if (!isset($options['type'])) {
            throw new Horde_Kolab_Storage_Exception(
                'The object type must be specified!'
            );
        }
        if (!isset($options['version'])) {
            $options['version'] = 1;
        }
    }

    /**
     * Create a new object in the specified folder.
     *
     * @param string $folder  The folder to use.
     * @param array  $object  The object.
     * @param array  $options Additional options for storing.
     * <pre>
     *  'type'    - Required argument specifying the object type that should be
     *              stored.
     *  'version' - Optional argument specifying the version of the object
     *              format.
     * </pre>
     *
     * @return string The ID of the new object or true in case the backend does
     *                not support this return value.
     */
    public function create($folder, $object, $options = array())
    {
        return $this->_driver->appendMessage(
            $folder,
            $this->createObject($object, $options)
        );
    }

    /**
     * Modify an existing object in the specified folder.
     *
     * @param string $folder  The folder to use.
     * @param array  $object  The object.
     * @param string $obid    The object ID in the backend.
     * @param array  $options Additional options for storing.
     * <pre>
     *  'type'    - Required argument specifying the object type that should be
     *              stored.
     *  'version' - Optional argument specifying the version of the object
     *              format.
     * </pre>
     *
     * @return string The ID of the modified object or true in case the backend
     *                does not support this return value.
     */
    public function modify($folder, $object, $obid, $options = array())
    {
        $modifiable = $this->_driver->getModifiable($folder, $obid, $object);
        $new_uid = $this->_format->modify($modifiable, $object, $options);
        $this->_driver->deleteMessages($folder, array($obid));
        $this->_driver->expunge($folder);
        return $new_uid;
    }

    /**
     * Create a new MIME representation for the object.
     *
     * @param array  $object  The object.
     * @param array  $options Additional options for storing.
     * <pre>
     *  'type'    - Required argument specifying the object type that should be
     *              stored.
     *  'version' - Optional argument specifying the version of the object
     *              format.
     * </pre>
     *
     * @return resource The MIME message representing the object.
     */
    public function createObject($object, $options = array())
    {
        $this->_completeOptions($options);
        $envelope = $this->_format->createEnvelope();
        $envelope->addPart($this->_format->createKolabPart($object, $options));
        return $envelope->toString(
            array(
                'canonical' => true,
                'stream' => true,
                'headers' => $this->_format->createEnvelopeHeaders(
                    $object['uid'],
                    $this->_driver->getAuth(),
                    $options['type']
                )
            )
        );
    }
}
