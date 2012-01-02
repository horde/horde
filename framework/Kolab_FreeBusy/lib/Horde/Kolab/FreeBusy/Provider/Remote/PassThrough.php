<?php
/**
 * This provider fetches the data from a remote server by passing it through.
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
 * This provider fetches the data from a remote server by passing it through.
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
class Horde_Kolab_FreeBusy_Provider_Remote_PassThrough
extends Horde_Kolab_FreeBusy_Provider_Remote
{
    /**
     * HTTP client
     *
     * @var Horde_Http_Client
     */
    private $_client;

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
     * @param Horde_Kolab_FreeBusy_Owner $owner   The owner of the data.
     * @param Horde_Controller_Request   $request The current request.
     * @param Horde_Kolab_FreeBusy_User  $user    The user accessing the data.
     * @param Horde_Http_Client          $client  An HTTP client.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Controller_Request $request,
        Horde_Kolab_FreeBusy_User $user,
        Horde_Http_Client $client
    )
    {
        $this->_user   = $user;
        $this->_client = $client;
        parent::__construct($owner, $request);
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
        Horde_Controller_Response $response, $params = array()
    )
    {
        $this->_passThrough($response);
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
        $this->_passThrough($response);
    }

    /**
     * Fetch remote data.
     *
     * @param Horde_Controller_Response  $response The response handler.
     *
     * @return NULL
     */
    public function _passThrough(Horde_Controller_Response $response)
    {
        $url = $this->getUrlWithCredentials(
            $this->_user->getPrimaryId(),
            $this->_user->getPassword()
        );
        $origin = $this->_client->get($url);
        if ($origin->code !== 200) {
            $url = $this->getUrlWithCredentials(
                $this->_user, 'XXX'
            );
            throw new Horde_Kolab_FreeBusy_Exception_Unauthorized(
                sprintf('Unable to read free/busy information from %s', $url)
            );
        }
        $response->setHeader('X-Redirect-To', $url);
        $response->setBody($origin->getStream());
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
