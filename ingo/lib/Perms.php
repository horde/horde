<?php
/**
 * Copyright 2014-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.

 * @category  Horde
 * @copyright 2013-2017 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Ingo
 */

/**
 * Permission handling for Ingo.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2017 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
class Ingo_Perms
{
    /**
     * Permission list.
     *
     * @var array
     */
    private $_perms;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_perms = array(
            'max_blacklist' => array(
                'handle' => function($allowed, $opts) {
                    return max(array_map('intval', $allowed));
                },
                'title' => _("Maximum number of blacklist addresses."),
                'type' => 'int'
            ),
            'max_forward' => array(
                'handle' => function($allowed, $opts) {
                    return max(array_map('intval', $allowed));
                },
                'title' => _("Maximum number of forward addresses."),
                'type' => 'int'
            ),
            'max_rules' => array(
                'handle' => function($allowed, $opts) {
                    return max(array_map('intval', $allowed));
                },
                'title' => _("Maximum number of rules (0 to disable rules editing)."),
                'type' => 'int'
            ),
            'max_whitelist' => array(
                'handle' => function($allowed, $opts) {
                    return max(array_map('intval', $allowed));
                },
                'title' => _("Maximum number of whitelist addresses."),
                'type' => 'int'
            )
        );
    }

    /**
     * @see Horde_Registry_Application#perms()
     */
    public function perms()
    {
        $perms = array(
            'backends' => array(
                'title' => _("Backends")
            )
        );

        foreach (array_keys(Ingo::loadBackends()) as $key) {
            $bkey = 'backends:' . $key;

            $perms[$bkey] = array(
                'title' => $key
            );

            foreach ($this->_perms as $key2 => $val2) {
                $perms[$bkey . ':' . $key2] = array(
                    'title' => $val2['title'],
                    'type' => $val2['type']
                );
            }
        }

        return $perms;
    }

    /**
     * @see Horde_Registry_Application#hasPermission()
     */
    public function hasPermission($permission, $allowed, $opts)
    {
        if (($pos = strrpos($permission, ':')) !== false) {
            $permission = substr($permission, $pos + 1);
        }

        return isset($this->_perms[$permission]['handle'])
            ? (bool)call_user_func($this->_perms[$permission]['handle'], $allowed, $opts)
            : (bool)$allowed;
    }

    /**
     * Get the full permission name for a permission.
     *
     * @param string $perm  The permission.
     *
     * @return string  The full (backend-specific) permission name.
     */
    public static function getPerm($perm)
    {
        return 'backends:' . $GLOBALS['session']->get('ingo', 'backend/id') . ':' . $perm;
    }

}
