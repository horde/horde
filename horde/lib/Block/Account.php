<?php
/**
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Eric Jon Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde
 */
class Horde_Block_Account extends Horde_Core_Block
{
    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);
        $this->_name = _("Account Information");
    }

    /**
     */
    protected function _title()
    {
        return _("My Account Information");
    }

    /**
     */
    protected function _content()
    {
        global $registry, $conf;

        $params = array_merge(
            (array)$conf['accounts']['params'],
            array('user' => $registry->getAuth()));

        switch ($conf['accounts']['driver']) {
        case 'null':
            $mydriver = new Horde_Block_Account_Base($params);
            break;

        case 'localhost':
        case 'finger':
        //case 'kolab':
            $class = 'Horde_Block_Account_' . Horde_String::ucfirst($conf['accounts']['driver']);
            $mydriver = new $class($params);
            break;

        case 'ldap':
            $params = Horde::getDriverConfig('accounts', 'ldap');
            $params['ldap'] = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Ldap')
                ->create('horde', 'accounts');
            $params['user'] = $registry->getAuth($params['strip'] ? 'bare' : null);
            $mydriver = new Horde_Block_Account_Ldap($params);
            break;

        default:
            return '';
        }

        try {
            // Check for password status.
            $status = $mydriver->checkPasswordStatus();

            $table = array(_("User Name") => $mydriver->getUsername());
            if ($fullname = $mydriver->getFullname()) {
                $table[_("Full Name")] = $fullname;
            }
            if ($home = $mydriver->getHome()) {
                $table[_("Home Directory")] = $home;
            }
            if ($shell = $mydriver->getShell()) {
                $table[_("Default Shell")] = $shell;
            }
            if ($quota = $mydriver->getQuota()) {
                $table[_("Quota")] = sprintf(
                    _("%.2fMB used of %.2fMB allowed (%.2f%%)"),
                    $quota['used'] / ( 1024 * 1024.0),
                    $quota['limit'] / ( 1024 * 1024.0),
                    ($quota['used'] * 100.0) / $quota['limit']);
            }
            if ($lastchange = $mydriver->getPasswordChange()) {
                $table[_("Last Password Change")] = $lastchange;
            }
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        $output = '<table class="item" width="100%" cellspacing="1">';

        if ($status) {
            $output .= '<tr><td colspan="2"><p class="notice">' .
                Horde::img('alerts/warning.png', _("Warning")) .
                '&nbsp;&nbsp;' . $status . '</p></td></tr>';
        }

        foreach ($table as $key => $value) {
            $output .= "<tr class=\"text\"><td>$key</td><td>$value</td></tr>\n";
        }
        $output .= "</table>\n";

        if (!$registry->isInactive('forwards') &&
            $registry->hasMethod('summary', 'forwards')) {
            try {
                $summary = $registry->callByPackage('forwards', 'summary');
                $output .= '<br />' . $summary . "\n";
            } catch (Exception $e) {
            }
        }

        if (!$registry->isInactive('vacation') &&
            $registry->hasMethod('summary', 'vacation')) {
            try {
                $summary = $registry->callByPackage('vacation', 'summary');
                $output .= '<br />' . $summary . "\n";
            } catch (Exception $e) {
            }
        }

        return $output;
    }
}
