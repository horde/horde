<?php
/**
 * Horde bundle API.
 *
 * This file defines information about Horde bundles.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package groupware
 */
class Horde_Bundle extends Horde_Core_Bundle
{
    /**
     * The bundle name.
     */
    const NAME = 'groupware';

    /**
     * The bundle version.
     */
    const VERSION = '4.0.9-git';

    /**
     * The bundle descriptive name.
     */
    const FULLNAME = 'Horde Groupware';

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
        $vars->auth__driver = 'sql';
        $vars->auth__params__driverconfig = 'horde';

        while (true) {
            $admin_user = $this->_cli->prompt('Specify a user name for the administrator account:');
            if (empty($admin_user)) {
                $this->_cli->writeln($this->_cli->red('An administration user is required'));
                continue;
            }
            $admin_pass = $this->_cli->passwordPrompt('Specify a password for the adminstrator account:');
            if (empty($admin_pass)) {
                $this->_cli->writeln($this->_cli->red('An administrator password is required'));
                continue;
            }
            $params = array(
                'db' => $GLOBALS['injector']->getInstance('Horde_Db_Adapter'),
                'encryption' => isset($GLOBALS['conf']['auth']['params']['encryption']) ? $GLOBALS['conf']['auth']['params']['encryption'] : 'ssha');
            $auth = Horde_Auth::factory('sql', $params);
            try {
                $exists = $auth->exists($admin_user);
            } catch (Horde_Exception $e) {
                $this->_cli->message('An error occured while trying to list the users. Error messages:', 'cli.error');
                $this->_cli->writeln($e->getMessage());
                return;
            }
            try {
                if ($exists) {
                    if ($this->_cli->prompt('This user exists already, do you want to update his password?', array('y' => 'Yes', 'n' => 'No'), 'y') == 'y') {
                        $auth->updateUser($admin_user, $admin_user, array('password' => $admin_pass));
                    } else {
                        break;
                    }
                } else {
                    $auth->addUser($admin_user, array('password' => $admin_pass));
                }
            } catch (Horde_Exception $e) {
                $this->_cli->message('An error occured while adding or updating the adminstrator. Error messages:', 'cli.error');
                $this->_cli->writeln($e->getMessage());
                return;
            }
            break;
        }

        return $admin_user;
    }
}
