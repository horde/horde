<?php
/**
 * Copyright 2006-2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Maps a single Kolab_Storage anonymous ACL element to the Horde permission
 * system.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */
class Horde_Perms_Permission_Kolab_Acl_Anonymous
extends Horde_Perms_Permission_Kolab_Acl
{
    /**
     * Converts the ACL string to a Horde_Perms mask and stores it in the
     * provided data array.
     *
     * @param array &$data The horde permission data.
     */
    public function toHorde(array &$data)
    {
        $data['guest'] = $this->convertAclToMask();
    }
}
