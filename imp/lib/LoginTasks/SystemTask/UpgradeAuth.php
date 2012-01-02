<?php
/**
 * Login system task for automated upgrade tasks.
 * These tasks REQUIRE IMP authentication.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_LoginTasks_SystemTask_UpgradeAuth extends Horde_Core_LoginTasks_SystemTask_Upgrade
{
    /**
     */
    protected $_app = 'imp';

    /**
     */
    protected $_auth = true;

    /**
     */
    protected $_versions = array(
        '5.0'
    );

    /**
     */
    protected function _upgrade($version)
    {
        switch ($version) {
        case '5.0':
            $this->_upgradeExpireImapCache();
            break;
        }
    }

    /**
     * Expire existing IMAP cache.
     */
    protected function _upgradeExpireImapCache()
    {
        try {
            $ob = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->ob;

            if ($cache = $ob->getCache()) {
                $ob->login();

                $mboxes = $ob->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, array('flat' => true));

                foreach ($mboxes as $val) {
                    $ob->cache->deleteMailbox($val);
                }
            }
        } catch (Exception $e) {}
    }

}
