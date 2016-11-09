<?php
/**
 * Copyright 2016 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSDL
 * @package  Whups
 */

/**
 * Login system task for automated upgrade tasks.
 *
 * This is only run for admins because it only needs to be run once for any
 * system, not per user.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/bsdl.php BSDL
 * @package  Whups
 */
class Whups_LoginTasks_SystemTask_UpgradeAdmin
extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'whups';

    /**
     */
    protected $_versions = array(
        '4.0'
    );

    /**
     * Constructor.
     */
    public function __construct()
    {
        global $registry;

        if (!$registry->isAdmin()) {
            $this->active = false;
            return;
        }

        parent::__construct();
    }

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '4.0':
            try {
                $this->_upgradeVfs();
            } catch (Horde_Exception $e) {
            }
            break;
        }
    }

    /**
     * Separate messages from attachments.
     */
    protected function _upgradeVfs()
    {
        global $conf, $injector;

        if (!isset($conf['vfs']['type'])) {
            return;
        }

        $vfs = $injector->getInstance('Horde_Core_Factory_Vfs')->create();
        $db = $injector->getInstance('Horde_Core_Factory_Db')
            ->create('whups', 'tickets');
        $tickets = $vfs->listFolder(Whups::VFS_ATTACH_PATH, null, false);
        foreach ($tickets as $ticket => $info) {
            if ($info['type'] != '**dir') {
                continue;
            }
            $source = Whups::VFS_ATTACH_PATH . '/' . $ticket;
            $target = Whups::VFS_MESSAGE_PATH . '/' . $ticket;
            $attachments = array_keys($vfs->listFolder($source, null, false));
            foreach ($attachments as $attachment) {
                if (strpos($attachment, 'Original Message.eml') === false) {
                    continue;
                }
                // Get highest message ID.
                if ($vfs->exists($target, 'id')) {
                    $id = $vfs->read($target, 'id') + 1;
                } else {
                    $id = 1;
                }
                $vfs->writeData($target, 'id', $id);
                // Migrate attachment to message.
                $vfs->rename($source, $attachment, $target, $id);
                $db->update(
                    'UPDATE whups_logs SET log_type = ?, log_value = ? '
                    . 'WHERE ticket_id = ? AND log_type = ? AND log_value = ?',
                    array(
                        'message',
                        (string)$id,
                        (int)$ticket,
                        'attachment',
                        $attachment
                    )
                );
            }
        }
    }
}
