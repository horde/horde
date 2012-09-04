<?php
/**
 * Defines AJAX actions used in the IMP advanced search page.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Ajax_Application_Handler_Search extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Create mailbox select list for advanced search page.
     *
     * Variables used:
     *   - unsub: (integer) If set, includes unsubscribed mailboxes.
     *
     * @return object  An object with the following entries:
     *   - mbox_list: (array)
     *   - tree: (string)
     */
    public function searchMailboxList()
    {
        $ob = $GLOBALS['injector']->getInstance('IMP_Ui_Search')->getSearchMboxList($this->vars->unsub);

        $result = new stdClass;
        $result->mbox_list = $ob->mbox_list;
        $result->tree = $ob->tree->getTree();

        return $result;
    }

}
