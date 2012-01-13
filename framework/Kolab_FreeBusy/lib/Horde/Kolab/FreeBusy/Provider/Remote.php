<?php
/**
 * Common functionality for the remote provider.
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
 * Common functionality for the remote provider.
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
abstract class Horde_Kolab_FreeBusy_Provider_Remote
implements Horde_Kolab_FreeBusy_Provider
{
    /**
     * The owner of the data.
     *
     * @var Horde_Kolab_FreeBusy_Owner
     */
    private $_owner;

    /**
     * The current request.
     *
     * @var Horde_Controller_Request
     */
    private $_request;

    /**
     * Constructor
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner   The owner of the data.
     * @param Horde_Controller_Request   $request The current request.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Controller_Request $request
    )
    {
        $this->_owner   = $owner;
        $this->_request = $request;
    }

    /**
     * Generate the remote URL.
     *
     * @return string The URL
     */
    protected function getUrl()
    {
        return $this->_getUrl(
            $this->_owner->getRemoteServer(),
            $this->_request->getPath()
        );
    }

    /**
     * Generate the URL for triggering data on a remote system.
     *
     * @param string  $username The user accessing the data.
     * @param string  $password The user password.
     *
     * @return string The URL
     */
    protected function getUrlWithCredentials(
        $username, $password
    )
    {
        return $this->_getUrl(
            preg_replace(
                '#(http[s]://)(.*)#',
                '\1' . urlencode($username) .
                 ':' .urlencode($password) . '@\2',
                $this->_owner->getRemoteServer()
            ),
            $this->_request->getPath()
        );
    }

    /**
     * Construct the final URL.
     *
     * @param string $server The server URL.
     * @param string $path   The path on the server.
     *
     * @return string The URL
     */
    private function _getUrl($server, $path)
    {
        return sprintf('%s/%s', $server, $path);
    }
}