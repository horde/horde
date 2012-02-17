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
        $mime_id = $this->matchMimeId($options['type'], $data->contentTypeMap());
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

    public function matchMimeId($type, $types)
    {
        switch ($type) {
        case 'contact':
            return array_search('application/x-vnd.kolab.contact', $types);
            break;
        case 'event':
            return array_search('application/x-vnd.kolab.event', $types);
            break;
        case 'note':
            return array_search('application/x-vnd.kolab.note', $types);
            break;
        case 'task':
            return array_search('application/x-vnd.kolab.task', $types);
            break;
        case 'h-prefs':
            return array_search('application/x-vnd.kolab.h-prefs', $types);
            break;
        case 'h-ledger':
            return array_search('application/x-vnd.kolab.h-ledger', $types);
            break;
        default:
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Unsupported object type %s!', $type)
            );
        }
    }

    public function getMimeType($type)
    {
        switch ($type) {
        case 'contact':
            return 'application/x-vnd.kolab.contact';
        case 'event':
            return 'application/x-vnd.kolab.event';
        case 'note':
            return 'application/x-vnd.kolab.note';
        case 'task':
            return 'application/x-vnd.kolab.task';
        case 'h-prefs':
            return 'application/x-vnd.kolab.h-prefs';
        case 'h-ledger':
            return 'application/x-vnd.kolab.h-ledger';
        default:
            throw new Horde_Kolab_Storage_Exception(
                sprintf('Unsupported object type %s!', $type)
            );
        }
    }

    /**
     * Generate the headers for the MIME envelope of a Kolab groupware object.
     *
     * @param string $uid The object uid.
     *
     * @return Horde_Mime_Headers The headers for the MIME envelope.
     */
    public function createEnvelopeHeaders($uid, $user, $type)
    {
        $headers = new Horde_Mime_Headers();
        $headers->setEOL("\r\n");
        $headers->addHeader('From', $user);
        $headers->addHeader('To', $user);
        $headers->addHeader('Date', date('r'));
        $headers->addHeader('X-Kolab-Type', $this->getMimeType($type));
        $headers->addHeader('Subject', $uid);
        $headers->addHeader('User-Agent', 'Horde::Kolab::Storage v' . Horde_Kolab_Storage::VERSION);
        $headers->addHeader('MIME-Version', '1.0');
        return $headers;
    }

    /**
     * Generate a new MIME envelope for a Kolab groupware object.
     *
     * @return Horde_Mime_Part The new MIME envelope.
     */
    public function createEnvelope()
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
                    'http://www.kolab.org/kolab2-clients.html'
                ),
                76,
                "\r\n"
            ),
            array('encoding' => 'quoted-printable')
        );
        $envelope->addPart($description);
        $envelope->buildMimeIds();
        return $envelope;
    }

    /**
     * Generate a new MIME part for a Kolab groupware object.
     *
     * @param array $object  The data that should be saved.
     * @param array $options Additional options for saving the data.
     *
     * @return Horde_Mime_Part The new MIME envelope.
     */
    public function createKolabPart($object, array $options)
    {
        $kolab = new Horde_Mime_Part();
        $kolab->setType($this->getMimeType($options['type']));
        if (empty($options['raw'])) {
            try {
                $kolab->setContents(
                    $this->_factory->createFormat(
                        'Xml', $options['type'], $options['version']
                    )->save($object),
                    array('encoding' => 'quoted-printable')
                );
            } catch (Horde_Kolab_Format_Exception $e) {
                throw new Horde_Kolab_Storage_Exception(
                    'Failed saving Kolab object!', 0, $e
                );
            }
        } else {
            $kolab->setContents(
                $object['content'],
                array('encoding' => 'quoted-printable')
            );
        }
        $kolab->setCharset('utf-8');
        $kolab->setDisposition('inline');
        $kolab->setDispositionParameter('x-kolab-type', 'xml');
        $kolab->setName('kolab.xml');
        return $kolab;
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
                           array $options)
    {
        $mime_id = $this->matchMimeId(
            $options['type'], $modifiable->getStructure()->contentTypeMap()
        );
        $modifiable->setPart(
            $mime_id, $this->createKolabPart($object, $options)
        );
        return $modifiable->store();
    }
}
