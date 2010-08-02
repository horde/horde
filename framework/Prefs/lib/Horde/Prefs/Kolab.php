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
 * @package  Prefs
 */
class Horde_Prefs_Kolab extends Horde_Prefs_Ldap
{
   /**
     * Constructor.
     *
     * @param string $scope  The scope for this set of preferences.
     * @param array $opts    See factory() for list of options.
     * @param array $params  A hash containing any additional configuration
     *                       or connection parameters a subclass might need.
     */
    protected function __construct($scope, $opts, $params)
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

        parent::__construct($scope, $opts, $params);
    }

}
