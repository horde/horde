<?php
/**
 * This class implements an authentication backend for Sabre_DAV based on
 * Horde_Auth.
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org/)
 *
 * @package Sabre
 * @author  Jan Schneider <jan@horde.org>
 * @license @todo
 */
class Sabre_DAV_Auth_Backend_Horde extends Sabre_DAV_Auth_Backend_Abstract
{
    /**
     * @var Horde_Auth
     */
    protected $_auth;

    public function __construct(Horde_Auth $auth)
    {
        $this->_auth = $auth;
    }

    /**
     * Returns the HTTP Digest hash for a username
     *
     * This must be the A1 part of the digest hash
     * 
     * @param string $username 
     * @return string 
     */
    public function getDigestHash($username)
    {
        // We don't have the A1 hash stored, and we don't have the plaintext
        // passwords. Workaround?
    }

    public function getUserList()
    {
        if (!$this->_auth->hasCapability('list')) {
            return array();
        }

        $users = array();
        foreach ($this->_auth->listUsers() as $user) {
            $users[] = array('href' => $user);
            // We could potentially get {DAV:}displayname from the users'
            // identities, but we should only do that if this method is not
            // supposed to be called too often.
        }

        return $users;
    }

}
