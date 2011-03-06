<?php
/**
 * Bridges a MIME message with Kolab format data parsing.
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
 * Bridges a MIME message with Kolab format data parsing.
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
class Horde_Kolab_Storage_Data_Format_Mime
implements Horde_Kolab_Storage_Data_Format
{
    /**
     * Factory for generating helper objects.
     *
     * @var Horde_Kolab_Storage_Factory
     */
    private $_factory;

    /**
     * The MIME based object provider.
     *
     * @var Horde_Kolab_Storage_Data_Parser_Structure
     */
    private $_structure;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Storage_Factory               $factory   Factory for
     *                                                             helper tools.
     * @param Horde_Kolab_Storage_Data_Parser_Structure $structure The MIME based
     *                                                             object handler.
     */
    public function __construct(
        Horde_Kolab_Storage_Factory $factory,
        Horde_Kolab_Storage_Data_Parser_Structure $structure
    ) {
        $this->_factory = $factory;
        $this->_structure = $structure;
    }
    
    /**
     * Parses the objects for the specified backend IDs.
     *
     * @param string $folder  The folder to access.
     * @param array  $uid     The object backend ID.
     * @param mixed  $data    The data that should get parsed.
     * @param array  $options Additional options for fetching.
     *
     * @return array The parsed object.
     */
    public function parse($folder, $obid, $data, array $options)
    {
        if (!$data instanceOf Horde_Mime_Part) {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'The provided data is not of type Horde_Mime_Part but %s instead!',
                    get_class($data)
                )
            );
        }
        $mime_id = $this->matchMimeId($options['type'], $data->contentTypeMap());
        if (empty($mime_id)) {
            //@todo: deal with exceptions
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'Unable to identify Kolab mime part in message %s in folder %s!',
                    $obid,
                    $folder
                )
            );
        }

        $mime_part = $data->getPart($mime_id);
        if (empty($mime_part)) {
            //@todo: deal with exceptions
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'Unable to identify Kolab mime part in message %s in folder %s!',
                    $obid,
                    $folder
                )
            );
        }
        $mime_part->setContents(
            $this->_structure->fetchId($folder, $obid, $mime_id)
        );
        $content = $mime_part->getContents(array('stream' => true));
        //@todo: deal with exceptions
        return $this->_factory->createFormat('Xml', $options['type'], $options['version'])
            ->load($content);
    }

    public function matchMimeId($type, $types)
    {
        switch ($type) {
        case 'event':
            return array_search('application/x-vnd.kolab.event', $types);
            break;
        case 'note':
            return array_search('application/x-vnd.kolab.note', $types);
            break;
        default:
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Unsupported object type %s!', $type)
            );
        }
    }

    /**
     * Generate a new MIME envelope for a Kolab groupware object.
     *
     * @return Horde_Mime_Part The new MIME envelope.
     */
    public function createEnvelope()
    {
        return new Horde_Mime_Part();
    }
}
