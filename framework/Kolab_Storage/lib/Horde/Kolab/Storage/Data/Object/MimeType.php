<?php
/**
 * Maps Kolab types to mime types.
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
 * Maps Kolab types to mime types.
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
class Horde_Kolab_Storage_Data_Object_MimeType
{
    public function getType($type)
    {
        $default_types = array(
            'contact', 'event', 'note', 'task', 'h-prefs', 'h-ledger'
        );
        if (in_array($type, $default_types)) {
            return new Horde_Kolab_Storage_Data_Object_MimeType_Default(
                $type
            );
        }
        throw new Horde_Kolab_Storage_Data_Exception(
            sprintf('Unsupported object type %s!', $type)
        );
    }
}