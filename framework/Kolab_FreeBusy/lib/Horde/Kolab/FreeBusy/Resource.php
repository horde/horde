<?php
/**
 * Interface definition for resources exporting data.
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
 * Interface definition for resources exporting data.
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
interface Horde_Kolab_FreeBusy_Resource
{
    /**
     * Return the name of the resource.
     *
     * @return string The name for the resource.
     */
    public function getName();

    /**
     * Return the owner of the resource.
     *
     * @return Horde_Kolab_FreeBusy_Owner The resource owner.
     */
    public function getOwner();

    /**
     * Return for whom this resource exports relevant data.
     *
     * @return string The user type the exported data of this resource is
     *                relevant for.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the relevance
     *                                        information failed.
     */
    public function getRelevance();

    /**
     * Fetch the resource ACL.
     *
     * @return array ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the ACL information
     *                                        failed.
     */
    public function getAcl();

    /**
     * Fetch the access controls on specific attributes of this
     * resource.
     *
     * @return array Attribute ACL for this resource.
     *
     * @throws Horde_Kolab_FreeBusy_Exception If retrieving the attribute ACL
     *                                        information failed.
     */
    public function getAttributeAcl();
}
