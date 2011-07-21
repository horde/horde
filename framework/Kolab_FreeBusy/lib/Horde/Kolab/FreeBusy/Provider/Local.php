<?php
/**
 * This provider deals with data from the local server.
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
 * This provider deals with data from the local server.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Provider_Local
implements Horde_Kolab_FreeBusy_Provider
{
    /**
     * Trigger a resource.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the data.
     * @param string                     $resource The resource to trigger.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function trigger(
        Horde_Controller_Response $response,
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Kolab_FreeBusy_User $user,
        $resource,
        $params = array()
    )
    {
    }

    /**
     * Fetch data for an owner.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the data.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function fetch(
        Horde_Controller_Response $response,
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Kolab_FreeBusy_User $user,
        $params = array()
    )
    {
    }

    /**
     * Delete data of an owner.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the data.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function delete(
        Horde_Controller_Response $response,
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Kolab_FreeBusy_User $user,
        $params = array()
    )
    {
    }
}