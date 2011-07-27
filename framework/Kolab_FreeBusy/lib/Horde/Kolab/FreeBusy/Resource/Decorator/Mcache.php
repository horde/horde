<?php
/**
 * Caches the resource return values in class variables.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Caches the resource return values in class variables.
 *
 * Copyright 2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Resource_Decorator_Mcache
implements Horde_Kolab_FreeBusy_Resource
{
    /**
     * The decorated resource.
     *
     * @var Horde_Kolab_FreeBusy_Resource_Interface
     */
    private $_resource;

    /**
     * The cached resource relevance.
     *
     * @var string
     */
    private $_relevance;

    /**
     * The cached resource ACL.
     *
     * @var array
     */
    private $_acl;

    /**
     * The cached resource attribute ACL.
     *
     * @var array
     */
    private $_attribute_acl;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Resource_Interface $resource The decorated resource.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Resource $resource
    ) {
        $this->_resource = $resource;
    }

    /**
     * Return the name of the resource.
     *
     * @return string The name for the resource.
     */
    public function getName()
    {
        return $this->_resource->getName();
    }

    /**
     * Return the owner of the resource.
     *
     * @return Horde_Kolab_FreeBusy_Owner The resource owner.
     */
    public function getOwner()
    {
        return $this->_resource->getOwner();
    }

    /**
     * Connect to the resource.
     *
     * @return NULL
     *
     * @throws Horde_Kolab_FreeBusy_Exception If connecting to the resource
     *                                        failed.
     */
    public function connect()
    {
        $this->_resource->connect();
    }

    /**
     * Return for whom this resource exports relevant data.
     *
     * @return string The user type the exported data of this resource is
     *                relevant for.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the relevance
     *                                        information failed.
     */
    public function getRelevance()
    {
        if (!isset($this->_relevance)) {
            $this->_relevance = $this->_resource->getRelevance();
        }
        return $this->_relevance;
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
        if (!isset($this->_acl)) {
            $this->_acl = $this->_resource->getAcl();
        }
        return $this->_acl;
    }

    /**
     * Fetch the access controls on specific attributes of this
     * resource.
     *
     * @return array Attribute ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the attribute ACL
     *                                        information failed.
     */
    public function getAttributeAcl()
    {
        if (!isset($this->_attribute_acl)) {
            $this->_attribute_acl = $this->_resource->getAttributeAcl();
        }
        return $this->_attribute_acl;
    }

    /**
     * Return the decorated resource.
     *
     * @return Horde_Kolab_FreeBusy_Resource_Interface The decorated resource.
     */
    protected function getResource()
    {
        return $this->_resource;
    }
}
