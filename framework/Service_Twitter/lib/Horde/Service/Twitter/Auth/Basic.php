<?php
/**
 * Horde_Service_Twitter_Auth class to abstract all auth related tasks
 *
 * Basically implements Horde_Oauth_Client and passes the calls along to the
 * protected oauth object.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org)
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @license  http://opensource.org/licenses/bsd-license.php BSD
 * @category Horde
 * @package Horde_Service_Twitter
 */
class Horde_Service_Twitter_Auth_Basic extends Horde_Service_Twitter_Auth
{
    protected static $_authorizationHeader;

    public function buildAuthorizationHeader()
    {
        if (empty(self::$_authorizationHeader)) {
            self::$_authorizationHeader = 'Basic ' . base64_encode($this->username . ':' . $this->password);
        }

        return self::$_authorizationHeader;
    }

}
