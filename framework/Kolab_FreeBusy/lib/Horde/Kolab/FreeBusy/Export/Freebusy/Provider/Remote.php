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
abstract class Horde_Kolab_FreeBusy_Export_Freebusy_Provider_Remote
implements Horde_Kolab_FreeBusy_Export_Freebusy_Provider
{
    /**
     * Generate the URL for triggering free/busy data on a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the f/b data.
     * @param string                     $folder   The folder to trigger.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getTriggerUrl(
        Horde_Kolab_FreeBusy_Owner $owner, $folder, $params = array()
    )
    {
        return $this->_getUrl(
            $owner->getFreebusyServer() . '/trigger',
            urlencode($owner->getPrimaryId()) . '/' . urlencode($folder),
            !empty($params['extended']) ? 'pxfb' : 'pfb'
        );
    }

    /**
     * Generate the URL for fetching free/busy data from a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the f/b data.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getFetchUrl(
        Horde_Kolab_FreeBusy_Owner $owner, $params = array()
    )
    {
        return $this->_getUrl(
            $owner->getFreebusyServer(),
            urlencode($owner->getPrimaryId()),
            !empty($params['extended']) ? 'xfb' : 'ifb'
        );
    }

    /**
     * Generate the URL for triggering free/busy data on a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the f/b data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the f/b
     *                                             data.
     * @param string                     $password The user password.
     * @param string                     $folder   The folder to trigger.
     * @param array                      $params   Additional parameters.
     *
     * @return string The URL
     */
    protected function getTriggerUrlWithCredentials(
        Horde_Kolab_FreeBusy_Owner $owner,
        Horde_Kolab_FreeBusy_User  $user,
        $password,
        $folder,
        $params = array()
    )
    {
        return $this->_getUrl(
            sprintf(
                '%s:%s@%s/trigger',
                urlencode($user->getPrimaryId()),
                $password,
                $owner->getFreebusyServer()
            ),
            urlencode($owner->getPrimaryId()) . '/' . urlencode($folder),
            !empty($params['extended']) ? 'pxfb' : 'pfb'
        );
    }

    /**
     * Generate the URL for fetching free/busy data from a remote system.
     *
     * @param Horde_Kolab_FreeBusy_Owner $owner    The owner of the f/b data.
     * @param Horde_Kolab_FreeBusy_User  $user     The user accessing the f/b
     *                                             data.
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
                $owner->getFreebusyServer()
            ),
            urlencode($owner->getPrimaryId()),
            !empty($params['extended']) ? 'xfb' : 'ifb'
        );
    }

    private function _getUrl($server, $folder, $suffix)
    {
        return sprintf('https://%s/%s.%s', $server, $folder, $suffix);
    }
}