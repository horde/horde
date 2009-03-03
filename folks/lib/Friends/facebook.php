<?php
/**
 * Folks internal firends implementaton
 *
 * NOTE: You must add enable facebook in global horde configuration
 *
 * $Horde: incubator/letter/lib/Friends/letter.php,v 1.9 2009/01/06 17:50:52 jan Exp $
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Folks
 */
class Folks_Friends_facebook extends Folks_Friends {

    /**
     * Get user friends
     *
     * @return array of users
     */
    protected function _getFriends()
    {
        global $conf, $prefs;

        if (!$conf['facebook']['enabled']) {
            return PEAR::raiseError(_("No Facebook integration exists."));
        }

        $context = array('http_client' => new Horde_Http_Client(),
                         'http_request' => new Horde_Controller_Request_Http());
        $facebook = new Horde_Service_Facebook($conf['facebook']['key'],
                                               $conf['facebook']['secret'],
                                               $context);

        $session = unserialize($prefs->getValue('facebook'));
        $facebook->auth->setUser($session['uid'], $session['sid'], 0);

        $fql = 'SELECT uid, name FROM user WHERE uid IN ('
            . 'SELECT uid2 FROM friend WHERE uid1=' . $session['uid'] . ')';

        $results = $facebook->fql->run($fql);
        $friends = array();
        foreach ($results as $result) {
            $friends[$result['uid']] = $result['uid'];
        }

        return $friends;
    }

    /**
     * Get avaiable groups
     */
    public function getGroups()
    {
        return array('whitelist' => _("Friends"));
    }
}