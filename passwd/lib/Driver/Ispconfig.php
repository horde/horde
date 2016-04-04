<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2016 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * Changes an email password using the ISPConfig 3 API.
 *
 * @author    Thomas Basler <tbasler@oprago.com>
 * @author    Michael Bunk <mb@computer-leipzig.com>
 * @category  Horde
 * @copyright 2016 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Ispconfig extends Passwd_Driver
{
    /**
     */
    public function __construct(array $params = array())
    {
        // Default ISPConfig encryption settings
        parent::__construct(array_merge(array(
            'encryption' => 'crypt-md5',
            'show_encryption' => false,
        ), $params));
        
        if (!class_exists('SoapClient')) {
            throw new Passwd_Exception('You need the soap PHP extension to use this driver.');
        }
        if (empty($this->_params['soap_uri']) ||
            empty($this->_params['soap_user']) ) {
            throw new Passwd_Exception('The Passwd Ispconfig driver is not properly configured, edit your passwd/config/backends.local.php.');
        }
    }

    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        // Connect
        $soap_uri = $this->_params['soap_uri'];
        $client = new SoapClient(null, array(
            'location' => $soap_uri . 'index.php',
            'uri'      => $soap_uri));
        
        // Login
        try {
            if (!$session_id = $client->login(
                $this->_params['soap_user'],
                $this->_params['soap_pass'])) {
                throw new Passwd_Exception(
                    sprintf(_("Login to %s failed."), $soap_uri));
            }
        } catch (SoapFault $e) {
            throw new Passwd_Exception($e);
        }
        
        // Get user information
        try {
            $users = $client->mail_user_get(
                $session_id,
                array('login' => $user));
        } catch (SoapFault $e) {
            throw new Passwd_Exception($e);
        }
        if (count($users) != 1) {
            throw new Passwd_Exception(
                sprintf(_("%d users with login %s found, one expected."),
                        count($users),
                        $user));
        }
        $user = $users[0];
        
        // Check the passwords match
        $this->_comparePasswords($user['password'], $oldpass);
        
        // Set new password
        $user['password'] = $newpass;
        
        // Save information
        try {
            $client->mail_user_update(
                    $session_id, $user['client_id'],
                    $user['mailuser_id'], $user);
        } catch (SoapFault $e) {
            throw new Passwd_Exception($e);
        }
        
        // Logout
        try {
            $client->logout(
                    $session_id);
        } catch (SoapFault $e) {
            throw new Passwd_Exception($e);
        }
    }
}
