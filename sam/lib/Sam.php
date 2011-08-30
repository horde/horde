<?php
/**
 * Sam base class.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Chris Bowlby <cbowlby@tenthpowertech.com>
 * @author  Max Kalika <max@horde.org>
 * @package Sam
 */
class Sam
{
    /**
     * Determine the backend to use.
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
        include SAM_BASE . '/config/backends.php';
        if (!isset($backends) || !is_array($backends)) {
            throw new Sam_Exception(_("No backends configured in backends.php")));
        }

        foreach ($backends as $temp) {
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
     * @return array    The attributes list.
     */
    static public function getAttributes()
    {
        static $_attributes;

        if (!isset($_attributes)) {
            $_attributes = array();
            require_once SAM_BASE . '/config/attributes.php';
        }

        return $_attributes;
    }

    /**
     * Find out whether the given attribute type is informational only.
     *
     * @param string $attribute           The attribute type to check.
     *
     * @return boolean  Returns true if the given type is known to be
     *                  informational only.
     */
    static public function infoAttribute($type = '')
    {
        return in_array($type, array('description', 'spacer', 'html',
                                     'header', 'link'));
    }

    /**
     * Map the given Horde username to the value used after applying any
     * configured hooks.
     *
     * @param mixed $hordeauth            Defines how to use the authenticated
     *                                    Horde username. If set to 'full',
     *                                    will initialize the username to
     *                                    contain the @realm part. Otherwise,
     *                                    the username will initialize as a
     *                                    simple login.
     *
     * @return string   The converted username.
     */
    static public function mapUser($hordeauth)
    {
        $uid = $GLOBALS['registry']->getAuth($hordeauth === 'full' ? null : 'bare');
        if (!empty($GLOBALS['conf']['hooks']['username'])) {
            return Horde::callHook('_sam_hook_username', array($uid), 'sam');
        }

        return $uid;
    }
}
