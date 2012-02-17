<?php
/**
 * The Horde_Notification_Event_Status:: class defines a single status
 * notification event.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Notification
 */
class Horde_Notification_Event_Status extends Horde_Notification_Event
{
    /**
     * Charset of the message.
     *
     * @var string
     */
    public $charset = null;

    /**
     * String representation of this object.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        $text = $this->message;

        if (!in_array('content.raw', $this->flags)) {
            $text = htmlspecialchars($text, ENT_COMPAT, $this->charset);
        }

        return $text;
    }

}
