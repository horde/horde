<?php
/**
 * The Horde_Notification_Listener_Javascript:: class provides functionality
 * for inserting javascript code from the message stack into the page.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Listener_Javascript extends Horde_Notification_Listener
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_handles = array(
            'javascript' => '',
            'javascript-file' => ''
        );
        $this->_name = 'javascript';
    }

    /**
     * Outputs the javascript code if there are any messages on the
     * 'javascript' message stack and if the 'notify_javascript' option is set.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options. Options: 'noscript'
     */
    public function notify(&$messageStack, $options = array())
    {
        if (!count($messageStack)) {
            return;
        }

        if (empty($options['noscript'])) {
            echo '<script type="text/javascript">//<![CDATA[' . "\n";
        }

        $files = array();

        while ($message = array_shift($messageStack)) {
            $msg_text = $this->getMessage($message);
            if ($message['type'] == 'javascript') {
                echo $msg_text . "\n";
            } elseif ($message['type'] == 'javascript-file') {
                $files[] = $msg_text;
            }
        }

        if (empty($options['noscript'])) {
            echo "//]]></script>\n";
            if (count($files)) {
                foreach ($files as $file) {
                    echo '<script type="text/javascript" src="' . $file . '"></script>' . "\n";
                }
            }
        }
    }

    /**
     * Outputs one message.
     *
     * @param array $message  One message hash from the stack.
     * @param array $options  An array of options (not used).
     *
     * @return string  The message text.
     */
    public function getMessage($message, $options = array())
    {
        $event = $this->getEvent($message);
        return $event->getMessage();
    }

}
