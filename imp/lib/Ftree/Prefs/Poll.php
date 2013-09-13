<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Manage the mailbox poll list.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ftree_Prefs_Poll extends IMP_Ftree_Prefs
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        global $prefs;

        if ($prefs->getValue('nav_poll_all')) {
            $this->_data = $this->_locked = true;
        } else {
            /* We ALWAYS poll the INBOX. */
            $this->_data = array('INBOX' => 1);

            /* Add the list of polled mailboxes from the prefs. */
            if ($nav_poll = @unserialize($prefs->getValue('nav_poll'))) {
                $this->_data += $nav_poll;
            }

            $this->_locked = $prefs->isLocked('nav_poll');
        }
    }

    /**
     */
    public function shutdown()
    {
        $GLOBALS['prefs']->setValue('nav_poll', serialize($this->_data));
    }

    /**
     * Prune non-existent mailboxes from poll list.
     */
    public function prunePollList()
    {
        $prune = array();

        if (!$this->locked) {
            foreach (IMP_Mailbox::get($this->_data) as $val) {
                if (!$val->mbox_ob->exists) {
                    $prune[] = $val;
                }
            }
        }

        $GLOBALS['injector']->getInstance('IMP_Ftree')->removePollList($prune);
    }

}
