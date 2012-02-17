<?php
/**
 * Maps folder permissions into the Horde_Permission system.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Maps folder permissions into the Horde_Permission system.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Perms_Permission_Kolab
extends Horde_Perms_Permission
{
    /** Kolab ACL speak for all permissions on a shared object. */
    const ALL = 'lrid';

    /**
     * The Kolab Folder these permissions belong to.
     *
     * @var Horde_Perms_Permission_Kolab_Storage
     */
    private $_storage;

    /**
     * The group handler.
     *
     * @var Horde_Group_Base
     */
    private $_groups;

    /**
     * A cache for the folder acl settings. The cache holds the permissions
     * in horde compatible format, not in the IMAP permission format.
     *
     * @var string
     */
    public $data;

    /**
     * Constructor.
     *
     * @param Horde_Perms_Permission_Kolab_Storage $storage The storage object
     *                                                      represented by this
     *                                                      permission instance.
     *
     * @param Horde_Group_Base $groups                      The group handler.
     */
    public function __construct(Horde_Perms_Permission_Kolab_Storage $storage,
                                Horde_Group_Base $groups)
    {
        parent::__construct(__CLASS__ . '::' . $storage->getPermissionId());
        $this->_storage = $storage;
        $this->_groups  = $groups;
        $this->data     = $this->getCurrentPermissions();
    }

    /**
     * Gets the current permission of the folder and stores the values in the
     * cache.
     *
     * @return NULL
     */
    public function getCurrentPermissions()
    {
        $data = array();
        /**
         * @todo: Can we lazy load $this->data so that we restrict to using
         * MYRIGHTS only when that is all we need and use the full GETACL just
         * when required.
         */
        $acl = new Horde_Perms_Permission_Kolab_AclIterator(
            $this->_storage->getAcl(),
            $this->_storage->getOwner()
        );
        foreach ($acl as $element) {
            $element->toHorde($data);
        }
        $data['type'] = 'matrix';
        return $data;
    }

    /**
     * Saves the current permission values from the cache to the IMAP folder.
     *
     * @return NULL
     */
    public function save()
    {
        /**
         * @todo: If somebody else accessed the folder before us, we will
         * overwrite the change here.
         */
        $current = $this->getCurrentPermissions();

        $elements = new Horde_Perms_Permission_Kolab_ElementIterator(
            $this->data, $this->_groups, $this->_storage->getOwner()
        );
        foreach ($elements as $element) {
            $this->_storage->setAcl($element->getId(), $element->fromHorde());
            $element->unsetInCurrent($current);
        }

        // Delete ACLs that have been removed
        $elements = new Horde_Perms_Permission_Kolab_ElementIterator(
            $current, $this->_groups, $this->_storage->getOwner()
        );
        foreach ($elements as $element) {
            $this->_storage->deleteAcl($element->getId());
        }

        // Load the permission from the folder again
        $this->data = $this->getCurrentPermissions();
    }

}
