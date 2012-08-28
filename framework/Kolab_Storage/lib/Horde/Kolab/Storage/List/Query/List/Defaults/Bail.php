<?php
/**
 * Protects against more than one default folder per type by bailing out.
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
 * Protects against more than one default folder per type by bailing out.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_List_Query_List_Defaults_Bail
extends Horde_Kolab_Storage_List_Query_List_Defaults
{
    /**
     * React on detection of more than one default folder.
     *
     * @param string  $first  The first default folder name.
     * @param string  $second The second default folder name.
     * @param string  $type   The folder type.
     * @param string  $owner  The folder owner.
     */
    protected function doubleDefault($first, $second, $owner, $type)
    {
        throw new Horde_Kolab_Storage_List_Exception(
            sprintf(
                'Both folders "%s" and "%s" of owner "%s" are marked as default folder of type "%s"!',
                $first,
                $second,
                $owner,
                $type
            )
        );
    }
}