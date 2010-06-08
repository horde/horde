<?php
/**
 * Passwd Base Class.
 *
 * $Horde: passwd/lib/Passwd.php,v 1.12.2.5 2009/01/06 15:25:21 jan Exp $
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @package Passwd
 */
class Passwd {

    /**
     * Determines if the given backend is the "preferred" backend for
     * this web server.  This decision is based on the global
     * 'SERVER_NAME' and 'HTTP_HOST' server variables and the contents
     * of the 'preferred' field in the backend's definition.  The
     * 'preferred' field may take a single value or an array of
     * multiple values.
     *
     * @param array $backend     A complete backend entry from the $backends
     *                           hash.
     *
     * @return boolean  True if this entry is "preferred".
     */
    function isPreferredBackend($backend)
    {
        if (!empty($backend['preferred'])) {
            if (is_array($backend['preferred'])) {
                foreach ($backend['preferred'] as $backend) {
                    if ($backend == $_SERVER['SERVER_NAME'] ||
                        $backend == $_SERVER['HTTP_HOST']) {
                        return true;
                    }
                }
            } elseif ($backend['preferred'] == $_SERVER['SERVER_NAME'] ||
                      $backend['preferred'] == $_SERVER['HTTP_HOST']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Change the Horde/IMP cached credentials. Should be called
     * only after a successful change of the password in the actual
     * backend storage. This routine is the same for all backends and
     * should not be implemented in the backend classes.
     *
     * @param string $username      The username we're changing.
     * @param string $oldpassword   The old user password.
     * @param string $new_password  The new user password to set.
     */
    function resetCredentials($old_password, $new_password)
    {
        if ($GLOBALS['registry']->getAuthCredential('password') == $old_password) {
            $GLOBALS['registry']->setAuthCredential('password', $new_password);
        }
    }

}
