<?php
/**
 * Horde bundle API.
 *
 * This file defines information about Horde bundles.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package webmail
 */
class Horde_Bundle extends Horde_Core_Bundle
{
    /**
     * The bundle name.
     */
    const NAME = 'webmail';

    /**
     * The bundle version.
     */
    const VERSION = '4.0.9-git';

    /**
     * The bundle descriptive name.
     */
    const FULLNAME = 'Horde Groupware Webmail Edition';

    /**
     * The short bundle descriptive name.
     */
    const SHORTNAME = 'Groupware';

    /**
     * Asks for the administrator settings.
     *
     * @return string  The administrator name.
     */
    protected function _configAuth(Horde_Variables $vars)
    {
        $vars->auth__driver = 'application';
        $vars->auth__params__app = 'imp';
        return $this->_cli->prompt('Specify an ' . $this->_cli->bold('existing') . ' mail user who you want to give administrator permissions (optional):');
    }
}
