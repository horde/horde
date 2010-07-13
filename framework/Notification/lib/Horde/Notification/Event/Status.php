<?php
/**
 * The Horde_Notification_Event_Status:: class defines a single status
 * notification event.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Event_Status extends Horde_Notification_Event
{
    /**
     * String representation of this object.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        $text = $this->message;

        if (!in_array('content.raw', $this->flags) && class_exists('Horde_Nls')) {
            $text = htmlspecialchars($text, ENT_COMPAT, $GLOBALS['registry']->getCharset());
        }

        return $text;
    }

}
