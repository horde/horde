<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Permission handling for IMP.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Perms
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
            'allow_folders' => array(
                'imaponly' => true,
                'title' => _("Allow folder navigation?"),
                'type' => 'boolean'
            ),
            'allow_remote' => array(
                'imaponly' => true,
                'title' => _("Allow remote account access?"),
                'type' => 'boolean'
            ),
            'create_mboxes' => array(
                'imaponly' => true,
                'title' => _("Allow mailbox creation?"),
                'type' => 'boolean'
            ),
            'max_bodysize' => array(
                'global' => true,
                'handle' => function($allowed, $opts) {
                    return isset($opts['value'])
                        ? (intval($allowed[0]) >= $opts['value'])
                        : $allowed;
                },
                'title' => _("Maximum size (bytes) of compose body"),
                'type' => 'int'
            ),
            'max_recipients' => array(
                'global' => true,
                'handle' => function($allowed, $opts) {
                    return isset($opts['value'])
                        ? (intval($allowed[0]) >= $opts['value'])
                        : $allowed;
                },
                'title' => _("Maximum Number of Recipients per Message"),
                'type' => 'int'
            ),
            'max_timelimit' => array(
                'global' => true,
                'handle' => function($allowed, $opts) {
                    if (!isset($opts['value'])) {
                        return $allowed;
                    }

                    $sentmail = $GLOBALS['injector']->getInstance('IMP_Sentmail');
                    if (!($sentmail instanceof IMP_Sentmail)) {
                        Horde::log('The permission for the maximum number of recipients per time period has been enabled, but no backend for the sent-mail logging has been configured for IMP.', 'ERR');
                        return true;
                    }

                    try {
                        $opts['value'] += $sentmail->numberOfRecipients($sentmail->limit_period, true);
                    } catch (IMP_Exception $e) {}

                    return (intval($allowed[0]) >= $opts['value']);
                },
                'title' => _("Maximum Number of Recipients per Time Period"),
                'type' => 'int'
            ),
            'max_create_mboxes' => array(
                'handle' => function($allowed, $opts) {
                    return (intval($allowed[0]) >= count($GLOBALS['injector']->getInstance('IMP_Ftree')));
                },
                'imaponly' => true,
                'title' => _("Maximum Number of Mailboxes"),
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

        foreach ($this->_perms as $key => $val) {
            if (!empty($val['global'])) {
                $perms[$key] = $val;
            }
        }

        // Run through every active backend.
        foreach (IMP_Imap::loadServerConfig() as $key => $val) {
            $bkey = 'backends:' . $key;

            $perms[$bkey] = array(
                'title' => $val->name
            );

            foreach ($this->_perms as $key2 => $val2) {
                if (empty($val2['global']) &&
                    (empty($val2['imaponly']) ||
                    ($val->protocol == 'imap'))) {
                    $perms[$bkey . ':' . $key2] = array(
                        'title' => $val2['title'],
                        'type' => $val2['type']
                    );
                }
            }
        }

        return $perms;
    }

    /**
     * @see Horde_Registry_Application#hasPermission()
     *
     * @param array $opts  Additional options:
     *   - For 'max_recipients' and 'max_timelimit', 'value'
     *     is the number of recipients in the current message.
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

}
