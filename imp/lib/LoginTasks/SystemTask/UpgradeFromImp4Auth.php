<?php
/**
 * Login system task for automated upgrade tasks.
 * These tasks DO require IMP authentication.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_LoginTasks_SystemTask_UpgradeFromImp4Auth extends Horde_LoginTasks_SystemTask
{
    /**
     */
    public $interval = Horde_LoginTasks::ONCE;

    /**
     */
    public function execute()
    {
        $this->_upgradeExpireImapCache();
    }

    /**
     */
    public function skip()
    {
        /* Skip task until we are authenticated. */
        return !$GLOBALS['registry']->isAuthenticated(array('app' => 'imp'));
    }

    /**
     * Expire existing IMAP cache.
     */
    protected function _upgradeExpireImapCache()
    {
        try {
            $ob = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()->ob;

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
