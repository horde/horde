<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Defines AJAX actions used to toggle expand/collapse state of mailboxes.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Ajax_Application_Handler_Mboxtoggle
extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Expand mailboxes (saves expanded state in prefs).
     *
     * Variables used:
     * <pre>
     *   - action: (string) [REQUIRED] Either 'collapse' or 'expand'.
     *   - all: (integer) 1 to toggle all mailboxes (mailbox information
     *          will not be returned).
     *   - mboxes: (string) The list of mailboxes to process (JSON encoded
     *             array; mailboxes are base64url encoded); required if 'all'
     *             is 0.
     * </pre>
     *
     * @return boolean  True.
     */
    public function toggleMailboxes()
    {
        $ftree = $GLOBALS['injector']->getInstance('IMP_Ftree');

        if ($this->vars->all) {
            $old_track = $ftree->eltdiff->track;
            $ftree->eltdiff->track = false;

            switch ($this->vars->action) {
            case 'collapse':
                $ftree->collapseAll();
                break;

            case 'expand':
                $ftree->expandAll();
                break;
            }

            $ftree->eltdiff->track = $old_track;
        } elseif (!empty($this->vars->mboxes)) {
            $mboxes = IMP_Mailbox::formFrom(json_decode($this->vars->mboxes));

            switch ($this->vars->action) {
            case 'collapse':
                $ftree->collapse($mboxes);
                break;

            case 'expand':
                $ftree->expand($mboxes);
                break;
            }
        }

        return true;
    }

}
