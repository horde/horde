<?php
/**
 * This provider deals with data from the local server.
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
 * This provider deals with data from the local server.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Provider_Local
implements Horde_Kolab_FreeBusy_Provider
{
    /**
     * The owner of the data.
     *
     * @var Horde_Kolab_FreeBusy_Owner
     */
    private $_owner;

    /**
     * The user accessing the data.
     *
     * @var Horde_Kolab_FreeBusy_User
     */
    private $_user;

    /**
     * Constructor
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner  The owner of the data.
     * @param Horde_Kolab_FreeBusy_User  $user   The user accessing the data.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Owner $owner, Horde_Kolab_FreeBusy_User $user
    )
    {
        $this->_owner  = $owner;
        $this->_user   = $user;
    }

    /**
     * Trigger a resource.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function trigger(
        Horde_Controller_Response $response,
        $params = array()
    )
    {
        $this->logger->debug(sprintf("Starting generation of free/busy data for user %s",
                                     $this->params->callee));

        $params = array('extended' => $this->params->type == 'xfb');

        // @todo: Reconsider this. We have been decoupled from the
        // global context here but reinjecting this value seems
        // extremely weird. Are there any other options?
        $this->app->callee = $this->params->callee;
        $this->data = $this->app->driver->fetch($this->params);

        $this->logger->debug('Delivering complete free/busy data.');

        /* Display the result to the user */
        $this->render();

        $this->logger->debug('Free/busy generation complete.');
    }

    /**
     * Fetch data for an owner.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function fetch(
        Horde_Controller_Response $response,
        $params = array()
    )
    {
    }

    /**
     * Delete data of an owner.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function delete(
        Horde_Controller_Response $response,
        $params = array()
    )
    {
    }

    /**
     * Regenerate all data accessible to the current user.
     *
     * @param Horde_Controller_Response  $response The response handler.
     * @param array                      $params   Additional parameters.
     *
     * @return NULL
     */
    public function regenerate(
        Horde_Controller_Response $response,
        $params = array()
    )
    {
    }
}