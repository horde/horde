<?php
/**
 * Horde bundle API.
 *
 * This file defines information about Horde bundles.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * @author  Jan Schneider <chuck@horde.org>
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
    const VERSION = '4.0-RC1';

    /**
     * The bundle descriptive name.
     */
    const FULLNAME = 'Horde Groupware';

    /**
     * Asks for the administrator settings.
     *
     * @return string  The administrator name.
     */
    protected function _configAuth(Horde_Variables $vars)
    {
        $vars->auth__driver = 'sql';

        while (true) {
            $admin_user = $cli->prompt('Specify a user name for the administrator account:');
            if (empty($admin_user)) {
                $cli->writeln($cli->red('An administration user is required'));
                continue;
            }
            $admin_pass = $cli->prompt('Specify a password for the adminstrator account:');
            if (empty($admin_pass)) {
                $cli->writeln($cli->red('An administrator password is required'));
            } else {
                $auth = &Auth::singleton($GLOBALS['conf']['auth']['driver']);
                $exists = $auth->exists($admin_user);
                if (is_a($exists, 'PEAR_Error')) {
                    $cli->message('An error occured while trying to list the users. Error messages:', 'cli.error');
                    $cli->writeln($exists->getMessage());
                    $cli->writeln($exists->getUserInfo());
                    return;
                }
                if ($exists) {
                    if ($cli->prompt('This user exists already, do you want to update his password?', array('y' => 'Yes', 'n' => 'No'), 'y') == 'y') {
                        $result = $auth->updateUser($admin_user, $admin_user, array('password' => $admin_pass));
                    } else {
                        break;
                    }
                } else {
                    $result = $auth->addUser($admin_user, array('password' => $admin_pass));
                }
                if (is_a($result, 'PEAR_Error')) {
                    $cli->message('An error occured while adding or updating the adminstrator. Error messages:', 'cli.error');
                    $cli->writeln($result->getMessage());
                    $cli->writeln($result->getUserInfo());
                    return;
                }
                break;
            }
        }
    }
}
