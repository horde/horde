<?php
/**
 * This provider fetches the data from a remote server by redirecting.
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
 * This provider fetches the data from a remote server by redirecting.
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
class Horde_Kolab_FreeBusy_Provider_Remote_Redirect
extends Horde_Kolab_FreeBusy_Provider_Remote
{
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
        $this->_redirect($response);
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
        $this->_redirect($response);
    }

    /**
     * Redirect the user.
     *
     * @param Horde_Controller_Response  $response The response handler.
     *
     * @return NULL
     */
    private function _redirect(Horde_Controller_Response $response)
    {
        $response->setRedirectUrl($this->getUrl());
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
        throw new Horde_Kolab_FreeBusy_Exception(
            'Action "regenerate" not supported for remote servers!'
        );
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
        throw new Horde_Kolab_FreeBusy_Exception(
            'Action "regenerate" not supported for remote servers!'
        );
    }
}