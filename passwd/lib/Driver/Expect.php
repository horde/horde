<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */

/**
 * An expect implementation of the passwd system.
 *
 * @author    Gaudenz Steinlin <gaudenz@soziologie.ch>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Passwd
 */
class Passwd_Driver_Expect extends Passwd_Driver
{
    /**
     */
    protected function _changePassword($user, $oldpass, $newpass)
    {
        // Sanity checks.
        if (!@is_executable($this->_params['program'])) {
            throw new Passwd_Exception(sprintf(_("%s does not exist or is not executable."), $this->_params['program']));
        }

        // Temporary logfile for error messages.
        $log = Horde::getTempFile('passwd');

        // Open expect script for writing.
        $prog = 'LANG=C LC_ALL=C ' . $this->_params['program'] .
            ' -f ' . escapeshellarg($this->_params['script']) .
            ' -- ' . $this->_params['params'] . ' -log ' . escapeshellarg($log);

        $exp = @popen($prog, 'w');
        @fwrite($exp, $user . "\n");
        @fwrite($exp, $oldpass . "\n");
        @fwrite($exp, $newpass . "\n");

        if (@pclose($exp)) {
            $errormsg = implode(' ', @file($log));
            @unlink($log);
            if ($errormsg) {
                throw new Passwd_Exception($errormsg);
            }
        }
    }

}
