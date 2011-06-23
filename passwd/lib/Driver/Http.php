<?php
/**
 * The Http driver attempts to change a user's password via a web based
 * interface and implements the Passwd_Driver API.
 *
 * Copyright 2000-2011 The Horde Project (http://www.horde.org/)
 *
 * WARNING: This driver has only formally been converted to Horde 4. 
 * No testing has been done. If this doesn't work, please file bugs at
 * bugs.horde.org
 * If you really need this to work reliably, think about sponsoring development
 * Please send a mail to lang -at- b1-systems.de if you can verify this driver to work
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Passwd
 * @since   Passwd 3.1
 */
class Passwd_Driver_Http extends Passwd_Driver {

    /**
     * Constructs a new Passwd_Driver_http object.
     *
     * @param array $params  A hash containing connection parameters.
     */
    function __construct($params = array())
    {
        $this->_params = $params;
    }

    /**
     * Change the user's password.
     *
     * @param string $username      The user for which to change the password.
     * @param string $old_password  The old (current) user password.
     * @param string $new_password  The new user password to set.
     *
     * @return mixed  True on success or throw Passwd_Exception on failure.
     */
    function changePassword($username,  $old_password, $new_password)
    {
        require_once 'HTTP/Request.php';
        // TODO: Can we make the Autoloader handle this?
        // TODO: Could this be converted to Horde_Http?

        $req = new HTTP_Request($this->_params['url']);
        $req->setMethod(HTTP_REQUEST_METHOD_POST);

        // Add the required fields that most web-based forms would use.
        $req->addPostData($this->_params['username'], $username);
        $req->addPostData($this->_params['oldPasswd'], $old_password);
        $req->addPostData($this->_params['passwd1'], $new_password);
        $req->addPostData($this->_params['passwd2'], $new_password);

        // Now add any fields that were passed in _params['fields'].
        foreach ($this->_params['fields'] as $fieldName => $fieldValue) {
            $req->addPostData($fieldName, $fieldValue);
        }

        // Send the request
        $result = $req->sendRequest();
        if (is_a($result, 'PEAR_Error')) {
            throw new Passwd_Exception($result->getMessage());
        }

        // Make sure we have a good response code
        $responseCode = $req->getResponseCode();
        if ($responseCode != 200) {
            throw new Passwd_Exception(_("The requested website for changing user passwords could not be reached."));
        }

        // We got *some* response from the server, so get the content and
        // let's see if we can't figure out if  it was a success or not.
        $responseBody = $req->getResponseBody();
        if (strpos($responseBody, $this->_params['eval_results']['badPass'])) {
            throw new Passwd_Exception(_("Incorrect old password."));
        } elseif (strpos($responseBody, $this->_params['eval_results']['badUser'])) {
            throw new Passwd_Exception(_("The username could not be found."));
        } elseif (!strpos($responseBody, $this->_params['eval_results']['success'])) {
            throw new Passwd_Exception(_("Your password could not be changed."));
        }
        return true;
    }
}
