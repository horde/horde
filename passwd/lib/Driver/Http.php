<?php
/**
 * Copyright 2000-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Driver to change a user's password via a web based interface.
 *
 * @author    Michael Rubinsky <mrubinsk@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2000-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Http extends Passwd_Driver
{
    /**
     */
    public function changePassword($username, $old_password, $new_password)
    {
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
        if ($result instanceof PEAR_Error) {
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
        }
        if (strpos($responseBody, $this->_params['eval_results']['badUser'])) {
            throw new Passwd_Exception(_("The username could not be found."));
        }
        if (!strpos($responseBody, $this->_params['eval_results']['success'])) {
            throw new Passwd_Exception(_("Your password could not be changed."));
        }
    }
}
