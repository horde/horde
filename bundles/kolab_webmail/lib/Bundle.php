<?php
/**
 * Horde bundle API.
 *
 * This file defines information about Horde bundles.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @package kolab_webmail
 */

class Horde_Bundle extends Horde_Core_Bundle
{
    /**
     * The bundle name.
     */
    const NAME = 'kolab_webmail';

    /**
     * The bundle version.
     */
    const VERSION = '4.0-RC1';

    /**
     * The bundle descriptive name.
     */
    const FULLNAME = 'Horde Kolab Edition';

    /**
     * The short bundle descriptive name.
     */
    const SHORTNAME = 'Kolab';

    /**
     * Asks for the administrator settings.
     *
     * @return string  The administrator name.
     */
    protected function _configAuth(Horde_Variables $vars)
    {
        $vars->auth__driver = 'kolab';
        $host = $this->_cli->prompt('Provide the host name of your Kolab server:');
        $maildomain = $this->_cli->prompt('Provide the primary mail domain of your Kolab server:');
        $basedn = $this->_cli->prompt('Provide the base DN of your Kolab server:');
        $phppw = $this->_cli->prompt('Provide the PHP pw of your Kolab LDAP nobody user:');
        $vars->problems__email = 'postmaster@' . $maildomain;
        $vars->problems__maildomain = $maildomain;
        $vars->kolab__enabled = 'true';
        $vars->kolab__ldap__server = 'ldap://' . $host . ':389';
        $vars->kolab__ldap__hostname = $host;
        $vars->kolab__ldap__port = '389';
        $vars->kolab__ldap__basedn = $basedn;
        $vars->kolab__ldap__phpdn = 'cn=nobody,cn=internal,' . $basedn;
        $vars->kolab__ldap__phppw = $phppw;
        $vars->kolab__imap__server = $host;
        $vars->kolab__imap__port = '993';
        $vars->kolab__imap__sieveport = '2000';
        $vars->kolab__imap__maildomain = $maildomain;
        $vars->mailer__type = 'smtp';
        $vars->mailer__params__auth = 'true';
        $vars->mailer__params__port = 587;
        $vars->mailer__params__host = $host;
        $vars->prefs__driver = 'KolabImap';
        return 'manager';
    }
}
