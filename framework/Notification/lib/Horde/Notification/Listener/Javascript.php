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
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Notification
 */
class Horde_Notification_Listener_Javascript extends Horde_Notification_Listener
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_handles['javascript'] = 'Horde_Notification_Event';
        $this->_handles['javascript-file'] = 'Horde_Notification_Event';
        $this->_name = 'javascript';
    }

    /**
     * Outputs the javascript code if there are any messages on the
     * 'javascript' message stack and if the 'notify_javascript' option is set.
     *
     * @param array $events   The list of events to handle.
     * @param array $options  An array of options:
     * <pre>
     * 'noscript' - TODO
     * </pre>
     */
    public function notify($events, $options = array())
    {
        $files = $js_text = array();

        foreach ($events as $event) {
            switch ($event->type) {
            case 'javascript':
                $js_text[] = strval($event);
                break;

            case 'javascript-file':
                $files[] = strval($event);
                break;
            }
        }

        if (empty($options['noscript']) && !empty($js_text)) {
            echo '<script type="text/javascript">//<![CDATA[' . "\n";
        }

        echo implode('', $js_text);

        if (empty($options['noscript'])) {
            if (!empty($js_text)) {
                echo "\n//]]></script>\n";
            }

            foreach ($files as $file) {
                echo '<script type="text/javascript" src="' . htmlspecialchars($file) . "\"></script>\n";
            }
        }
    }

}
