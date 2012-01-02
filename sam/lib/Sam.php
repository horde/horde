<?php
/**
 * Sam base class.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author  Max Kalika <max@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Sam
 */
class Sam
{
    /**
     * Determines the backend to use.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' either field
     * in the backend's definition.  The 'preferred' field may take a
     * single value or an array of multiple values.
     *
     * @return array  The backend entry.
     * @throws Sam_Exception
     */
    static public function getPreferredBackend()
    {
        $backends = Horde::loadConfiguration('backends.php', 'backends');

        if (!isset($backends) || !is_array($backends)) {
            throw new Sam_Exception(_("No backends configured in backends.php"));
        }

        foreach ($backends as $temp) {
            if (!empty($temp['disabled'])) {
                continue;
            }
            if (!isset($backend)) {
                $backend = $temp;
            } elseif (!empty($temp['preferred'])) {
                if (is_array($temp['preferred'])) {
                    foreach ($temp['preferred'] as $val) {
                        if (($val == $_SERVER['SERVER_NAME']) ||
                            ($val == $_SERVER['HTTP_HOST'])) {
                            $backend = $temp;
                        }
                    }
                } elseif (($temp['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($temp['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backend = $temp;
                }
            }
        }

        /* Check for valid backend configuration. */
        if (!isset($backend)) {
            throw new Sam_Exception(_("No backend configured for this host"));
        }
        if (empty($backend['driver'])) {
            throw new Sam_Exception(sprintf(_("No \"%s\" element found in backend configuration."), 'driver'));
        }

        /* Make sure the 'params' entry exists. */
        if (!isset($backend['params'])) {
            $backend['params'] = array();
        }

        return $backend;
    }

    /**
     * Find a list of configured attributes.
     *
     * Load the attributes configuration file or uses an already-loaded
     * cached copy. If loading for the first time, cache it for later use.
     *
     * @return array  The attributes list.
     */
    static public function getAttributes()
    {
        static $_attributes;

        if (!isset($_attributes)) {
            $_attributes = Horde::loadConfiguration('attributes.php', '_attributes');
        }

        return $_attributes;
    }

    /**
     * Find out whether the given attribute type is informational only.
     *
     * @param string $attribute  The attribute type to check.
     *
     * @return boolean  Returns true if the given type is known to be
     *                  informational only.
     */
    static public function infoAttribute($type = '')
    {
        return in_array(
            $type,
            array('description', 'spacer', 'html', 'header', 'link'));
    }

    /**
     * Converts the current user's name, optionally removing the domain part or
     * applying any configured hooks.
     *
     * @param string|boolean $hordeauth  Defines how to use the authenticated
     *                                   Horde username. If set to 'full',
     *                                   will initialize the username to
     *                                   contain the @realm part. Otherwise,
     *                                   the username will initialize as a
     *                                   simple login.
     *
     * @return string   The converted username.
     */
    static public function mapUser($hordeauth)
    {
        $uid = $GLOBALS['registry']->getAuth($hordeauth === 'full' ? null : 'bare');
        try {
            return Horde::callHook('username', array($uid), 'sam');
        } catch (Horde_Exception_HookNotSet $e) {
            return $uid;
        }
    }
}
