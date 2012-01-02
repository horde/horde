<?php
/**
 * The backend for Kolab resources.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * The backend for Kolab resources.
 *
 * Copyright 2004-2008 Klarälvdalens Datakonsult AB
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Resource_Kolab
implements Horde_Kolab_FreeBusy_Resource
{
    /**
     * The link to the folder.
     *
     * @var Horde_Kolab_Storage_Folder
     */
    private $_folder;

    /**
     * The folder owner.
     *
     * @var Horde_Kolab_FreeBusy_Owner_Freebusy
     */
    protected $_owner;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Folder          $folder The storage folder
     *                                                    representing this
     *                                                    resource.
     * @param Horde_Kolab_FreeBusy_Owner_Freebusy $owner  The resource owner.
     */
    public function __construct(
        Horde_Kolab_Storage_Folder $folder,
        Horde_Kolab_FreeBusy_Owner $owner
    ) {
        $this->_folder = $folder;
        $this->_owner  = $owner;
    }

    /**
     * Return the owner of the resource.
     *
     * @return Horde_Kolab_FreeBusy_Owner The resource owner.
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Return the name of the resource.
     *
     * @return string The name for the resource.
     */
    public function getName()
    {
        return $this->_folder->getName();
    }

    /**
     * Return the folder represented by this resource.
     *
     * @return Horde_Kolab_Storage_Folder The folder.
     */
    protected function getFolder()
    {
        return $this->_folder;
    }

    /**
     * Return the data represented by this resource.
     *
     * @return Horde_Kolab_Storage_Data The data.
     */
    protected function getData()
    {
        return $this->_folder->getData();
    }

    /**
     * Return for whom this resource exports relevant data.
     *
     * @return string The user type the exported data of this resource is
     *                relevant for.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the relevance
     *                                        information failed.
     *
     * @todo It would be nice if we would not only have the free/busy specific
     * relevance but a generic way of setting the relevance of resources.
     */
    public function getRelevance()
    {
        throw new Horde_Kolab_FreeBusy_Exception(
            'There is no generic definition for relevance available!'
        );
    }

    /**
     * Fetch the resource ACL.
     *
     * @return array ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the ACL information
     *                                        failed.
     */
    public function getAcl()
    {
        $perm = $this->_folder->getPermission();
        $acl = &$perm->acl;
        return $acl;
    }

    /**
     * Fetch the access controls on specific attributes of this
     * resource.
     *
     * @return array Attribute ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the attribute ACL
     *                                        information failed.
     *
     * @todo It would be nice if we would not only have the free/busy specific
     * attribute acls but a generic way of setting attribute ACL for resources.
     */
    public function getAttributeAcl()
    {
        throw new Horde_Kolab_FreeBusy_Exception(
            'There is no generic definition for attribute ACL available!'
        );
    }
}
