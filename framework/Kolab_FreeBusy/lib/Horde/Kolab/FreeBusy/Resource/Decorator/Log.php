<?php
/**
 * Logs the resource access.
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
 * Logs the resource access.
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
class Horde_Kolab_FreeBusy_Resource_Decorator_Log
implements Horde_Kolab_FreeBusy_Resource
{
    /**
     * The decorated resource.
     *
     * @var Horde_Kolab_FreeBusy_Resource_Interface
     */
    private $_resource;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Resource_Interface $resource The decorated
     *                                                          resource.
     * @param mixed                                   $logger   The log handler. The
     *                                                          class must at least
     *                                                          provide the debug()
     *                                                          method.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Resource $resource,
        $logger
    ) {
        $this->_resource = $resource;
        $this->_logger   = $logger;
    }

    protected function getLogger()
    {
        return $this->_logger;
    }

    protected function getResource()
    {
        return $this->_resource;
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
        $this->logger->debug(
            sprintf(
                'Successfully connected to resource %s',
                $this->_resource->getName()
            )
        );
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
        $relevance = $this->_resource->getRelevance();
        if (empty($relevance)) {
            $this->_logger->debug(
                sprintf(
                    'No relevance value found for %s',
                    $this->_resource->getName()
                )
            );
        } else {
            $this->_logger->debug(
                sprintf(
                    'Relevance for %s is %s',
                    $this->_resource->getName(),
                    $relevance
                )
            );
        }
        return $relevance;
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
        $acl = $this->_resource->getAcl();
        if (empty($acl)) {
            $this->_logger->debug(
                sprintf(
                    'No ACL found for %s',
                    $this->_resource->getName()
                )
            );
        } else {
            $this->_logger->debug(
                sprintf(
                    'ACL for %s is %s',
                    $this->_resource->getName(),
                    serialize($acl)
                )
            );
        }
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
     */
    public function getAttributeAcl()
    {
        $attribute_acl = $this->_resource->getAttributeAcl();
        if (empty($attribute_acl)) {
            $this->_logger->debug(
                sprintf(
                    'No attribute ACL found for %s',
                    $this->_resource->getName()
                )
            );
        } else {
            $this->_logger->debug(
                sprintf(
                    'Attribute ACL for %s is %s',
                    $this->_resource->getName(),
                    serialize($attribute_acl)
                )
            );
        }
        return $attribute_acl;
    }
}
