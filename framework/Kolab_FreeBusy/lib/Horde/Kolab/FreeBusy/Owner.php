<?php
/**
 * This basic interface for a resource owner.
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
 * This basic interface for a resource owner.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
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
interface Horde_Kolab_FreeBusy_Owner
{
    /**
     * Is the user known in the user database?
     *
     * @return boolean True if the user data is present.
     */
    public function isKnown();

    /**
     * Return the original owner parameter.
     *
     * @return string The original owner parameter.
     */
    public function getOwner();

    /**
     * Return the primary id of the resource owner.
     *
     * @return string The primary id.
     */
    public function getPrimaryId();

    /**
     * Return the mail address of the resource owner.
     *
     * @return string The mail address.
     */
    public function getMail();

    /**
     * Return the name of the resource owner.
     *
     * @return string The name of the owner.
     */
    public function getName();

    /**
     * Indicates the correct remote server for the resource owner.
     *
     * @param string $type The requested resource type.
     *
     * @return string The server name.
     */
    public function getRemoteServer($type = '');
}