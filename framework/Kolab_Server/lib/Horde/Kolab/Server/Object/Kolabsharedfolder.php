<?php
/**
 * A shared IMAP folder.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with shared folders
 * entries for Kolab.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Object_Kolabsharedfolder extends Horde_Kolab_Server_Object
{
    /** Define attributes specific to this object type */

    /** The common name */
    const ATTRIBUTE_CN = 'cn';

    /** The home server of this folder */
    const ATTRIBUTE_HOMESERVER = 'kolabHomeServer';

    /** The specific object class of this object type */
    const OBJECTCLASS_KOLABSHAREDFOLDER = 'kolabSharedFolder';

    /**
     * A structure to initialize the attribute structure for this class.
     *
     * @var array
     */
    static public $init_attributes = array(
        'defined' => array(
            self::ATTRIBUTE_CN,
            self::ATTRIBUTE_HOMESERVER,
        ),
        'required' => array(
            self::ATTRIBUTE_CN,
        ),
        'object_classes' => array(
            self::OBJECTCLASS_KOLABSHAREDFOLDER,
        ),
    );

    /**
     * Return the filter string to retrieve this object type.
     *
     * @static
     *
     * @return string The filter to retrieve this object type from the server
     *                database.
     */
    public static function getFilter()
    {
        return '(' . self::ATTRIBUTE_OC . '=' . self::OBJECTCLASS_KOLABSHAREDFOLDER .')';
    }

    /**
     * Generates an ID for the given information.
     *
     * @param array $info The data of the object.
     *
     * @static
     *
     * @return string|PEAR_Error The ID.
     */
    public static function generateId($info)
    {
        return trim(self::ATTRIBUTE_CN . '=' . $info['cn'], " \t\n\r\0\x0B,");
    }

    /**
     * Convert the object attributes to a hash.
     *
     * @param string $attrs The attributes to return.
     *
     * @return array|PEAR_Error The hash representing this object.
     */
    public function toHash($attrs = null)
    {
        if (!isset($attrs)) {
            $attrs = array(
                self::ATTRIBUTE_CN,
                self::ATTRIBUTE_HOMESERVER,
                self::ATTRIBUTE_FOLDERTYPE,
            );
        }
        return parent::toHash($attrs);
    }
}
