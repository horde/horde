<?php
/**
 * Common functionality for the remote provider.
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
 * Common functionality for the remote provider.
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
abstract class Horde_Kolab_FreeBusy_Provider_Remote
implements Horde_Kolab_FreeBusy_Provider
{
    /**
     * Generate the URL for triggering data on a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param string                     $resource The resource to trigger.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getTriggerUrl(
        Horde_Kolab_FreeBusy_Owner $owner, $resource, $params = array()
    )
    {
        return $this->_getUrl(
            $owner->getRemoteServer() . '/trigger',
            urlencode($owner->getPrimaryId()) . '/' . urlencode($resource),
            !empty($params['extended']) ? 'pxfb' : 'pfb'
        );
    }

    /**
     * Generate the URL for fetching data from a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getFetchUrl(
        Horde_Kolab_FreeBusy_Owner $owner, $params = array()
    )
    {
        return $this->_getUrl(
            $owner->getRemoteServer(),
            urlencode($owner->getPrimaryId()),
            !empty($params['extended']) ? 'xfb' : 'ifb'
        );
    }

    /**
     * Generate the URL for triggering data on a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the data.
     * @param string                     $password The user password.
     * @param string                     $resource The resource to trigger.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getTriggerUrlWithCredentials(
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Kolab_FreeBusy_User  $user,
        $password,
        $resource,
        $params = array()
    )
    {
        return $this->_getUrl(
            sprintf(
                '%s:%s@%s/trigger',
                urlencode($user->getPrimaryId()),
                $password,
                $owner->getRemoteServer()
            ),
            urlencode($owner->getPrimaryId()) . '/' . urlencode($resource),
            !empty($params['extended']) ? 'pxfb' : 'pfb'
        );
    }

    /**
     * Generate the URL for fetching data from a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the data.
     * @param string                     $password The user password.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getFetchUrlWithCredentials(
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Kolab_FreeBusy_User  $user,
        $password,
        $params = array()
    )
    {
        return $this->_getUrl(
            sprintf(
                '%s:%s@%s',
                urlencode($user->getPrimaryId()),
                $password,
                $owner->getRemoteServer()
            ),
            urlencode($owner->getPrimaryId()),
            !empty($params['extended']) ? 'xfb' : 'ifb'
        );
    }

    /**
     * Construct the final URL.
     *
     * @param string $server The server URL.
     * @param string $path   The path on the server.
     * @param string $suffix The file suffix.
     *
     * @return string The URL
     */
    private function _getUrl($server, $path, $suffix)
    {
        return sprintf('%s/%s.%s', $server, $path, $suffix);
    }
}