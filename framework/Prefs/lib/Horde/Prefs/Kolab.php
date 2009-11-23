<?php
/**
 * Kolab implementation of the Horde preference system. Derives from the
 * Prefs_ldap LDAP authentication object, and simply provides parameters to it
 * based on the global Kolab configuration.
 *
 * Copyright 2004-2007 Stuart Binge <s.binge@codefusion.co.za>
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Stuart Binge <s.binge@codefusion.co.za>
 * @category Horde
 * @package  Horde_Prefs
 */
class Horde_Prefs_Kolab extends Horde_Prefs_Ldap
{
    /**
     * Constructor.
     *
     * @param string $scope     The current application scope.
     * @param string $user      The user who owns these preferences.
     * @param string $password  The password associated with $user.
     * @param array $params     A hash containing connection parameters.
     * @param boolean $caching  Should caching be used?
     */
    public function __construct($scope, $user, $password, $params, $caching)
    {
        require_once 'Horde/Kolab.php';
        $params = array(
            'hostspec' => Kolab::getServer('ldap'),
            'port' => $GLOBALS['conf']['kolab']['ldap']['port'],
            'version' => '3',
            'basedn' => $GLOBALS['conf']['kolab']['ldap']['basedn'],
            'writedn' => 'user',
            'searchdn' => $GLOBALS['conf']['kolab']['ldap']['phpdn'],
            'searchpw' => $GLOBALS['conf']['kolab']['ldap']['phppw'],
            'uid' => 'mail'
        );

        parent::__construct($scope, $user, $password, $params, $caching);
    }

}
