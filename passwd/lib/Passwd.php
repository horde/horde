<?php
/**
 * Passwd base class.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Mike Cochrane <mike@graftonhall.co.nz>
 * @package Passwd
 */
class Passwd
{
    static public function getBackends()
    {
        $allbackends = Horde::loadConfiguration('backends.php', 'backends', 'passwd');
        if (!isset($allbackends) || !is_array($allbackends)) {
            throw new Passwd_Exception(_("No backends configured in backends.php"));
        }

        $backends = array();
        foreach ($allbackends as $name => $backend) {
            if (!empty($backend['disabled'])) {
                continue;
            }

            /* Make sure the 'params' entry exists. */
            if (!isset($backend['params'])) {
                $backend['params'] = array();
            }

            if (!empty($backend['preferred'])) {
                if (is_array($backend['preferred'])) {
                    foreach ($backend['preferred'] as $val) {
                        if (($val == $_SERVER['SERVER_NAME']) ||
                            ($val == $_SERVER['HTTP_HOST'])) {
                            $backends[$name] = $backend;
                        }
                    }
                } elseif (($backend['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($backend['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backends[$name] = $backend;
                }
            } else {
                $backends[$name] = $backend;
            }
        }

        /* Check for valid backend configuration. */
        if (empty($backends)) {
            throw new Passwd_Exception(_("No backend configured for this host"));
        }

        return $backends;
    }

    /**
     * Determines if the given backend is the "preferred" backend for this web
     * server.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' field in the
     * backend's definition.  The 'preferred' field may take a single value or
     * an array of multiple values.
     *
     * @param array $backend  A complete backend entry from the $backends hash.
     *
     * @return boolean  True if this entry is "preferred".
     */
    static public function isPreferredBackend($backend)
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
     * Changes the cached Horde credentials.
     *
     * Should be called only after a successful change of the password in the
     * actual backend storage.
     *
     * @param string $username      The username we're changing.
     * @param string $oldpassword   The old user password.
     * @param string $new_password  The new user password to set.
     */
    static public function resetCredentials($old_password, $new_password)
    {
        if ($GLOBALS['registry']->getAuthCredential('password') == $old_password) {
            $GLOBALS['registry']->setAuthCredential('password', $new_password);
        }
    }
}
