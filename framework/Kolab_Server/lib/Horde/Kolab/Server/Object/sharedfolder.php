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
class Horde_Kolab_Server_Object_sharedfolder extends Horde_Kolab_Server_Object
{

    /**
     * The LDAP filter to retrieve this object type
     *
     * @var string
     */
    public static $filter = '(objectClass=kolabSharedFolder)';

    /**
     * The ldap classes for this type of object.
     *
     * @var array
     */
    protected $object_classes = array(
        Horde_Kolab_Server_Object::OBJECTCLASS_TOP,
        Horde_Kolab_Server_Object::OBJECTCLASS_KOLABSHAREDFOLDER,
    );

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
        return trim($info['cn'], " \t\n\r\0\x0B,");
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
                Horde_Kolab_Server_Object::ATTRIBUTE_CN,
                Horde_Kolab_Server_Object::ATTRIBUTE_HOMESERVER,
                Horde_Kolab_Server_Object::ATTRIBUTE_FOLDERTYPE,
            );
        }
        return parent::toHash($attrs);
    }
}
