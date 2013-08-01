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
 * Defines AJAX actions used to toggle expand/collapse state of mailboxes.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Mboxtoggle extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Expand mailboxes (saves expanded state in prefs).
     *
     * Variables used:
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded).
     *
     * @return boolean  True.
     */
    public function expandMailboxes()
    {
        if (!empty($this->vars->mboxes)) {
            $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

            foreach (Horde_Serialize::unserialize($this->vars->mboxes, Horde_Serialize::JSON) as $val) {
                $imptree->expand(IMP_Mailbox::formFrom($val));
            }
        }

        return true;
    }

    /**
     * AJAX action: Collapse mailboxes.
     *
     * Variables used:
     *   - all: (integer) 1 to show all mailboxes.
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded) if 'all' is 0.
     *
     * @return boolean  True.
     */
    public function collapseMailboxes()
    {
        $imptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');

        if ($this->vars->all) {
            $imptree->collapseAll();
        } elseif (!empty($this->vars->mboxes)) {
            foreach (Horde_Serialize::unserialize($this->vars->mboxes, Horde_Serialize::JSON) as $val) {
                $imptree->collapse(IMP_Mailbox::formFrom($val));
            }
        }

        return true;
    }

}
