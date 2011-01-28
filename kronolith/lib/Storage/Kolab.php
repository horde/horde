<?php
/**
 * Horde Kronolith free/busy driver for the Kolab IMAP Server.
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Stuart Binge <omicron@mighty.co.za>
 * @package Kronolith
 */
class Kronolith_Storage_Kolab extends Kronolith_Storage
{
    protected $_params = array();

    public function __construct($user, array $params = array())
    {
        $this->_user = $user;
        $this->_params = $params;
    }

    /**
     * @throws Kronolith_Exception
     */
    public function search($email, $private_only = false)
    {
        global $conf;

        if (class_exists('Horde_Kolab_Session')) {
            $session = Horde_Kolab_Session::singleton();
            $server = $session->freebusy_server;
        } else {
            $server = sprintf('%s://%s:%d/freebusy/',
                              $conf['storage']['freebusy']['protocol'],
                              Kolab::getServer('imap'),
                              $conf['storage']['freebusy']['port']);
        }

        $fb_url = sprintf('%s/%s.xfb', $server, $email);

        $options['method'] = 'GET';
        $options['timeout'] = 5;
        $options['allowRedirects'] = true;

        if (!empty($GLOBALS['conf']['http']['proxy']['proxy_host'])) {
            $options = array_merge($options, $GLOBALS['conf']['http']['proxy']);
        }

        $http = new HTTP_Request($fb_url, $options);
        $http->setBasicAuth($GLOBALS['registry']->getAuth(), $GLOBALS['registry']->getAuthCredential('password'));
        @$http->sendRequest();
        if ($http->getResponseCode() != 200) {
            throw new Horde_Exception_NotFound();
        }
        $vfb_text = $http->getResponseBody();

        $iCal = new Horde_Icalendar;
        $iCal->parsevCalendar($vfb_text);

        $vfb = $iCal->findComponent('VFREEBUSY');
        if ($vfb === false) {
            throw new Horde_Exception_NotFound();
        }

        return $vfb;
    }

    public function store($email, $vfb, $public = false)
    {
        // We don't care about storing FB info at the moment; we rather let
        // Kolab's freebusy.php script auto-generate it for us.
    }

}
