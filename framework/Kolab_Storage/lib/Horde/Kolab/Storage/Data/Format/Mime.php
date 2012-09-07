<?php
/**
 * Bridges a MIME message with Kolab format data parsing.
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
 * Bridges a MIME message with Kolab format data parsing.
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
     * The MIME type handler.
     *
     * @var Horde_Kolab_Storage_Data_Object_MimeType
     */
    private $_mime_type;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Storage_Factory               $factory   Factory for
     *                                                             helper tools.
     * @param Horde_Kolab_Storage_Data_Parser_Structure $structure The MIME based
     *                                                             object handler.
     */
    public function __construct(Horde_Kolab_Storage_Factory $factory,
                                Horde_Kolab_Storage_Data_Parser_Structure $structure)
    {
        $this->_factory = $factory;
        $this->_structure = $structure;
        $this->_mime_type = new Horde_Kolab_Storage_Data_Object_MimeTypes();
    }

    /**
     * Parses the objects for the specified backend IDs.
     *
     * @param string $folder  The folder to access.
     * @param array  $uid     The object backend ID.
     * @param mixed  $data    The data that should get parsed.
     * @param array  $options Additional options for fetching.
     *
     * @return array The parsed object or a raw stream.
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
        $mime_id = $this->_mime_type->getType($options['type'])
            ->matchMimeId($data->contentTypeMap());
        if (empty($mime_id)) {
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
        if (empty($options['raw'])) {
            try {
                return $this->_factory->createFormat('Xml', $options['type'], $options['version'])
                    ->load($content);
            } catch (Horde_Kolab_Format_Exception $e) {
                throw new Horde_Kolab_Storage_Exception(
                    sprintf(
                        'Failed parsing Kolab object %s in folder %s: %s',
                        $obid,
                        $folder,
                        $e->getMessage()
                    ),
                    0,
                    $e
                );
            }
        } else {
            return array('content' => $content);
        }
    }

    /**
     * Modify a Kolab groupware object.
     *
     * @param Horde_Kolab_Storage_Driver_Modifiable $modifiable The modifiable object.
     * @param array                                 $object     The updated object.
     * @param array                                 $options    Additional options.
     *
     * @return string The ID of the modified object or true in case the backend
     *                does not support this return value.
     */
    public function modify(Horde_Kolab_Storage_Data_Modifiable $modifiable,
                           $object,
                           array $options,
                           $folder,
                           $obid)
    {
        $mime_id = $this->_mime_type->getType($options['type'])
            ->matchMimeId($modifiable->getStructure()->contentTypeMap());
        $original = $modifiable->getOriginalPart($mime_id);
        $original->setContents(
            $this->_structure->fetchId($folder, $obid, $mime_id)
        );
        $content = new Horde_Kolab_Storage_Data_Object_Content_Modified(
            $this->_mime_type->getType($options['type']),
            $object,
            $original->getContents(array('stream' => true)),
            $this->_factory->createFormat(
                'Xml', $options['type'], $options['version']
            )
        );
        $part = new Horde_Kolab_Storage_Data_Object_Part();

        $modifiable->setPart(
            $mime_id, $part->setContents($content)
        );
        return $modifiable->store();
    }
}
