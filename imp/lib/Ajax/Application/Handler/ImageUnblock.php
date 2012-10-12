<?php
/**
 * Defines AJAX actions used for unblocking images in HTML messages.
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
class IMP_Ajax_Application_Handler_ImageUnblock extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Store e-mail sender of message in safe address list.
     *
     * Variables used:
     *   - mbox: (string) Mailbox of message (base64url encoded).
     *   - uid: (integer) UID of message.
     *
     * @return boolean  True on success.
     */
    public function imageUnblockAdd()
    {
        global $injector, $notification;

        $contents = $injector->getInstance('IMP_Factory_Contents')->create(new IMP_Indices(IMP_Mailbox::formFrom($this->vars->mbox), $this->vars->uid));
        $address = IMP::bareAddress($contents->getHeader()->getValue('from'));
        $injector->getInstance('IMP_Prefs_Special_ImageReplacement')->addSafeAddrList(IMP::bareAddress($address));

        $notification->push(sprintf(_("Always showing images in messages sent by %s."), $address), 'horde.success');

        return true;
    }

}
