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

        $files = $js_text = array();

        while ($message = array_shift($messageStack)) {
            $event = $this->getMessage($message);
            switch ($event->type) {
            case 'javascript':
                $js_text[] = $event->message . "\n";
                break;

            case 'javascript-file':
                $files[] = $event->message;
                break;
            }
        }

        if (empty($options['noscript']) && !empty($js_text)) {
            echo '<script type="text/javascript">//<![CDATA[' . "\n";
        }

        echo implode('', $js_text);

        if (empty($options['noscript'])) {
            if (!empty($js_text)) {
                echo "//]]></script>\n";
            }

            if (count($files)) {
                foreach ($files as $file) {
                    echo '<script type="text/javascript" src="' . $file . '"></script>' . "\n";
                }
            }
        }
    }

    /**
     * Processes one message from the message stack.
     *
     * @param Horde_Notification_Event $event  An event object.
     * @param array $options                   An array of options (not used).
     *
     * @return mixed  The formatted message.
     */
    public function getMessage($event, $options = array())
    {
        return $event->message;
    }

}
