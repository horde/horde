<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Driver to change a user's password via a web based interface.
 *
 * @author    Michael Rubinsky <mrubinsk@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Http extends Passwd_Driver
{
    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        // Add the required fields that most web-based forms would use.
        // Then add any fields that were passed in _params['fields'].
        $post_data = array_merge(array(
            $this->_params['username'] => $user,
            $this->_params['oldPasswd'] => $oldpass,
            $this->_params['passwd1'] => $newpass,
            $this->_params['passwd2'] => $newpass
        ), $this->_params['fields']);

        // Send the request
        try {
            $response = $GLOBALS['injector']->getInstance('Horde_Core_Factory_HttpClient')->create()->post($this->_params['url'], $post_data);
        } catch (Horde_Http_Exception $e) {
            throw new Passwd_Exception($e);
        }

        // Make sure we have a good response code
        if ($response->code != 200) {
            throw new Passwd_Exception(_("The requested website for changing user passwords could not be reached."));
        }

        // We got *some* response from the server, so get the content and
        // let's see if we can't figure out if  it was a success or not.
        $body = $response->getBody();
        if (strpos($body, $this->_params['eval_results']['badPass'])) {
            throw new Passwd_Exception(_("Incorrect old password."));
        }
        if (strpos($body, $this->_params['eval_results']['badUser'])) {
            throw new Passwd_Exception(_("The username could not be found."));
        }
        if (!strpos($body, $this->_params['eval_results']['success'])) {
            throw new Passwd_Exception(_("Your password could not be changed."));
        }
    }

}
