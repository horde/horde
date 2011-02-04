<?php
/**
 * Defines a Kolab storage object that supports permission handling.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Defines a Kolab storage object that supports permission handling.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Perms
 */
interface Horde_Perms_Permission_Kolab_Storage
{
    /**
     * Return the ID of this storage object.
     *
     * @return string The ID.
     */
    public function getPermissionId();

    /**
     * Return the owner of this storage object.
     *
     * @return string The owner.
     */
    public function getOwner();

    /**
     * Retrieve the Kolab specific access rights for this storage object.
     *
     * @return An array of rights.
     */
    public function getAcl();

    /**
     * Set the Kolab specific access rights for this storage object.
     *
     * @param string $user The user to set the ACL for.
     * @param string $acl  The ACL.
     *
     * @return NULL
     */
    public function setAcl($user, $acl);

    /**
     * Delete Kolab specific access rights for this storage object.
     *
     * @param string $user The user to delete the ACL for
     *
     * @return NULL
     */
    public function deleteAcl($user);
}