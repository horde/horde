<?php
/**
 * Horde Kronolith free/busy driver for the Kolab IMAP Server.
 * Copyright 2004-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.horde.org/licenses/gpl.
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
        $server = $GLOBALS['injector']->getInstance('Horde_Kolab_Session')
            ->getFreebusyServer();
        if (empty($server)) {
            throw new Horde_Exception_NotFound();
        }

        $http = $GLOBALS['injector']
            ->getInstance('Horde_Core_Factory_HttpClient')
            ->create(array(
                'request.username' => $GLOBALS['registry']->getAuth(),
                'request.password' => $GLOBALS['registry']->getAuthCredential('password')
            ));

        try {
            $response = $http->get(sprintf('%s/%s.xfb', $server, $email));
        } catch (Horde_Http_Client_Exception $e) {
            throw new Horde_Exception_NotFound();
        }
        if ($response->code != 200) {
            throw new Horde_Exception_NotFound();
        }
        $vfb_text = $response->getBody();

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
