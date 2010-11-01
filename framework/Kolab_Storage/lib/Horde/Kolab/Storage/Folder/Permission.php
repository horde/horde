<?php
/**
 * Maps folder permissions into the Horde_Permission system.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Maps folder permissions into the Horde_Permission system.
 *
 * Copyright 2006-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Permission
extends Horde_Perms_Permission
{
    /**
     * The Kolab Folder these permissions belong to.
     *
     * @var Horde_Kolab_Storage_Folder
     */
    private $_folder;

    /**
     * The Horde_Group:: handler.
     *
     * @var Horde_Group
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
     * @param string                     $name   The name of the folder.
     * @param Horde_Kolab_Storage_Folder $acl    The folder these permissions
     *                                           belong to.
     * @param Horde_Group                $groups The group handler.
     */
    public function __construct(
        $name,
        Horde_Kolab_Storage_Folder $folder,
        Horde_Group $groups
    ) {
        parent::__construct(__CLASS__ . '::' . $name);
        $this->_folder = $folder;
        $this->_groups = $groups;
        $this->data    = $this->getCurrentPermissions();
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
        $acl = new Horde_Kolab_Storage_Folder_Permission_AclIterator(
            $this->_folder->getAcl(),
            $this->_groups,
            $this->_folder->getOwner()
        );
        foreach ($acl as $element) {
            $element->toHorde($data);
        }
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

        $elements = new Horde_Kolab_Storage_Folder_Permission_ElementIterator(
            $this->data, $this->_groups, $this->_folder->getOwner()
        );
        foreach ($elements as $element) {
            $this->_folder->setAcl($element->getId(), $element->fromHorde());
            $element->unsetInCurrent($current);
        }

        // Delete ACLs that have been removed
        $elements = new Horde_Kolab_Storage_Folder_Permission_ElementIterator(
            $current, $this->_groups, $this->_folder->getOwner()
        );
        foreach ($elements as $element) {
            $this->_folder->deleteAcl($element->getId());
        }

        // Load the permission from the folder again
        $this->data = $this->getCurrentPermissions();
    }

}
