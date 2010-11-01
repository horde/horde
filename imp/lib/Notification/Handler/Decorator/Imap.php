<?php
/**
 * The Imap Decorator adds IMAP alert notifications to the stack.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Notification_Handler_Decorator_Imap
extends Horde_Notification_Handler_Decorator_Base
{
    /**
     * Listeners are handling their messages.
     *
     * @param array $options  An array containing display options for the
     *                        listeners (see Horde_Notification_Handler for
     *                        details).
     */
    public function notify($options)
    {
        if (in_array('status', $options['listeners']) &&
            ($ob = $GLOBALS['injector']->getInstance('IMP_Injector_Factory_Imap')->create()) &&
            $ob->ob) {
            /* Display IMAP alerts. */
            foreach ($ob->alerts() as $alert) {
                $GLOBALS['notification']->push($alert, 'horde.warning');
            }
        }
    }

}
