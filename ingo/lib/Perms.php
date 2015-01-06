<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.

 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   Ingo
 */

/**
 * Permission handling for Ingo.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
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
                'title' => _("Maximum number of blacklist addresses."),
                'type' => 'int'
            ),
            'max_forward' => array(
                'title' => _("Maximum number of forward addresses."),
                'type' => 'int'
            ),
            'max_rules' => array(
                'title' => _("Maximum number of rules (0 to disable rules editing)."),
                'type' => 'int'
            ),
            'max_whitelist' => array(
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

        switch ($permission) {
        case 'max_blacklist':
        case 'max_forward':
        case 'max_rules':
        case 'max_whitelist':
            $allowed = max(array_map('intval', $allowed));
            break;
        }

        return $allowed;
    }

    /**
     * Get the full permission name for a permission.
     *
     * @param string $perm  The permission.
     *
     * @return string  The full (backend-specific) permission name.
     */
    static public function getPerm($perm)
    {
        return 'backends:' . $GLOBALS['session']->get('ingo', 'backend/id') . ':' . $perm;
    }

}
