<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Initialize session data for Ingo.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Session
{
    /**
     * Create an ingo session.
     *
     * Session entries:
     * <pre>
     *   - backend: (array) The backend configuration to use.
     *   - change: (integer) The timestamp of the last time the rules were
     *             altered.
     *   - personal_share: (string) Personal share signature.
     *   - storage: (array) Used by Ingo_Storage for caching data.
     *   - script_categories: (array) The list of available categories for the
     *                        Ingo_Script driver in use.
     * </pre>
     *
     * @throws Ingo_Exception
     */
    static public function create()
    {
        global $injector, $prefs, $registry, $session;

        /* _getBackend() will throw an Exception, so do these first as errors
         * are fatal. */
        foreach (array_filter(self::_getBackend()) as $key => $val) {
            $session->set('ingo', 'backend/' . $key, $val);
        }

        /* Disable categories as specified in preferences */
        $locked_prefs = array(
            'blacklist' => Ingo_Storage::ACTION_BLACKLIST,
            'forward' => Ingo_Storage::ACTION_FORWARD,
            'spam' => Ingo_Storage::ACTION_SPAM,
            'vacation' => Ingo_Storage::ACTION_VACATION,
            'whitelist' => Ingo_Storage::ACTION_WHITELIST
        );
        $locked = array();
        foreach ($locked_prefs as $key => $val) {
            if ($prefs->isLocked($key)) {
                $locked[] = $val;
            }
        }

        /* Set the list of categories this driver supports. */
        $ingo_scripts = $injector->getInstance('Ingo_Factory_Script')->createAll();
        $categories = array();
        foreach ($ingo_scripts as $ingo_script) {
            $categories = array_merge(
                $categories,
                $ingo_script->availableActions(),
                $ingo_script->availableCategories()
            );
        }
        $session->set('ingo', 'script_categories', array_diff($categories, $locked));

        /* Create shares if necessary. */
        $factory = $injector->getInstance('Ingo_Factory_Transport');
        foreach ($session->get('ingo', 'backend/transport', Horde_Session::TYPE_ARRAY) as $transport) {
            if ($factory->create($transport)->supportShares()) {
                $shares = $injector->getInstance('Horde_Core_Factory_Share')->create();

                /* If personal share doesn't exist then create it. */
                $sig = $session->get('ingo', 'backend/id') . ':' . $registry->getAuth();
                if (!$shares->exists($sig)) {
                    $identity = $injector->getInstance('Horde_Core_Factory_Identity')->create();
                    $name = $identity->getValue('fullname');
                    if (trim($name) == '') {
                        $name = $registry->getAuth('original');
                    }

                    $shares->addShare(
                        $shares->newShare($registry->getAuth(), $sig, $name)
                    );
                }

                $session->set('ingo', 'personal_share', $sig);
                break;
            }
        }
    }

    /**
     * Determine the backend to use.
     *
     * This decision is based on the global 'SERVER_NAME' and 'HTTP_HOST'
     * server variables and the contents of the 'preferred' either field
     * in the backend's definition.  The 'preferred' field may take a
     * single value or an array of multiple values.
     *
     * @return array  The backend entry.
     * @throws Ingo_Exception
     */
    static protected function _getBackend()
    {
        $backend = null;

        foreach (Ingo::loadBackends() as $name => $val) {
            $val['id'] = $name;

            if (!isset($backend)) {
                $backend = $val;
            } elseif (!empty($val['preferred'])) {
                if (is_array($val['preferred'])) {
                    foreach ($val['preferred'] as $v) {
                        if (($v == $_SERVER['SERVER_NAME']) ||
                            ($v == $_SERVER['HTTP_HOST'])) {
                            $backend = $val;
                        }
                    }
                } elseif (($val['preferred'] == $_SERVER['SERVER_NAME']) ||
                          ($val['preferred'] == $_SERVER['HTTP_HOST'])) {
                    $backend = $val;
                }
            }
        }

        /* Check for valid backend configuration. */
        if (is_null($backend)) {
            throw new Ingo_Exception(_("No backend configured for this host"));
        }

        foreach (array('script', 'transport') as $val) {
            if (empty($backend[$val])) {
                throw new Ingo_Exception(sprintf(_("No \"%s\" element found in backend configuration."), $val));
            }
        }

        return $backend;
    }

}
