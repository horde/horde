<?php
/**
 * Mime type handling for Kolab objects.
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
 * Mime type handling for Kolab objects.
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
class Horde_Kolab_Storage_Object_MimeType
{
    private static $_object_types = array(
        'contact' => 'application/x-vnd.kolab.contact',
        'distribution-list' => 'application/x-vnd.kolab.contact.distlist',
        'event' => 'application/x-vnd.kolab.event',
        'journal' => 'application/x-vnd.kolab.journal',
        'note' => 'application/x-vnd.kolab.note',
        'task' => 'application/x-vnd.kolab.task',
        'configuration' => 'application/x-vnd.kolab.configuration',
        'h-prefs' => 'application/x-vnd.kolab.h-prefs',
        'h-ledger' => 'application/x-vnd.kolab.h-ledger'
    );

    private static $_folder_types = array(
        'contact' => array(
            'application/x-vnd.kolab.contact',
            'application/x-vnd.kolab.contact.distlist'
        ),
        'event' => array('application/x-vnd.kolab.event'),
        'journal' => array('application/x-vnd.kolab.journal'),
        'note' => array('application/x-vnd.kolab.note'),
        'task' => array('application/x-vnd.kolab.task'),
        'h-prefs' => array('application/x-vnd.kolab.h-prefs'),
        'h-ledger' => array('application/x-vnd.kolab.h-ledger')
    );

    /**
     * Determine the mime type given the object type.
     *
     * @param string $type The object type.
     *
     * @return string The mime type associated to the object type.
     */
    static public function getMimeTypeFromObjectType($type)
    {
        if (isset(self::$_object_types[$type])) {
            return self::$_object_types[$type];
        } else {
            throw new Horde_Kolab_Storage_Data_Exception(
                sprintf('Unsupported object type %s!', $type)
            );
        }
    }    

    /**
     * Try to determine the MIME part that carries the object of the specified type.
     *
     * @param Horde_Mime_Part $structure A structural representation of the mime message.
     * @param string $type The object type.
     *
     * @return string|boolean The MIME ID of the message part carrying the object of the specified type or false if such a part was not identified within the message.
     */
    static public function matchMimePartToObjectType(Horde_Mime_Part $structure, $type)
    {
        return array_search(
            self::getMimeTypeFromObjectType($type),
            $structure->contentTypeMap()
        );
    }    

    /**
     * Determine the mime type given the type of the folder that holds the object.
     *
     * @param string $type The folder type.
     *
     * @return array The mime types associated to the folder type.
     */
    static public function getMimeTypesFromFolderType($type)
    {
        if (isset(self::$_folder_types[$type])) {
            return self::$_folder_types[$type];
        } else {
            throw new Horde_Kolab_Storage_Data_Exception(
                sprintf('Unsupported folder type %s!', $type)
            );
        }
    }    

    /**
     * Try to determine the MIME part that carries an object matching the specified folder type.
     *
     * @param Horde_Mime_Part $structure A structural representation of the mime message.
     * @param string $type The folder type.
     *
     * @return string|boolean The MIME ID of the message part carrying an object matching the specified folder type or false if such a part was not identified within the message.
     */
    static public function matchMimePartToFolderType(Horde_Mime_Part $structure, $type)
    {
        $mime_types = self::getMimeTypesFromFolderType($type);
        $id = false;
        foreach ($mime_types as $mime_type) {
            $id = array_search(
                $mime_type, $structure->contentTypeMap()
            );
            if ($id) {
                return array(
                    $id,
                    self::getObjectTypeFromMimeType($mime_type)
                );
            }
        }
	return false;
    }    

    /**
     * Try to determine the MIME part that carries an object matching based on the message headers.
     *
     * @param Horde_Mime_Part $structure A structural representation of the mime message.
     * @param Horde_Mime_Headers $headers The message headers.
     *
     * @return string|boolean The MIME ID of the message part carrying an object matching the message headers or false if such a part was not identified within the message.
     */
    static public function matchMimePartToHeaderType(Horde_Mime_Part $structure, Horde_Mime_Headers $headers)
    {
        $mime_type = $headers->getValue('X-Kolab-Type');
        if ($mime_type === null) {
            return false;
        }
        return array(
            array_search($mime_type, $structure->contentTypeMap()),
            self::getObjectTypeFromMimeType($mime_type)
        );
    }    

    /**
     * Determine the object type based on a specific MIME part that carries a Kolab object.
     *
     * @param Horde_Mime_Part $structure A structural representation of the mime message.
     * @param string $id The MIME part carrying the Kolab object.
     *
     * @return string|boolean The object type or false if no matching object type was found.
     */
    static public function getObjectTypeFromMimePart(Horde_Mime_Part $structure, $id)
    {
        return self::getObjectTypeFromMimeType($structure->getPart($id)->getType());
    }    

    /**
     * Determine the object type based on the mime type of a Kolab object.
     *
     * @param string $mime_type The MIME type of the Kolab object.
     *
     * @return string|boolean The object type or false if no matching object type was found.
     */
    static public function getObjectTypeFromMimeType($mime_type)
    {
        return array_search($mime_type, self::$_object_types);
    }    


}