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
            $GLOBALS['injector']->getInstance('IMP_Ftree')->expand(
                IMP_Mailbox::formFrom(json_decode($this->vars->mboxes))
            );
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
        $ftree = $GLOBALS['injector']->getInstance('IMP_Ftree');

        if ($this->vars->all) {
            $ftree->collapseAll();
        } elseif (!empty($this->vars->mboxes)) {
            $ftree->collapse(
                IMP_Mailbox::formFrom(json_decode($this->vars->mboxes))
            );
        }

        return true;
    }

}
