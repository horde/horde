<?php
/**
 * The provider definition.
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
 * The provider definition.
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
interface Horde_Kolab_FreeBusy_Provider
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
    );

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
    );

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
    );

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
    );
}