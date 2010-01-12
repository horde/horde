<?php
/**
 * The Horde_Notification_Listener_Status:: class provides functionality for
 * displaying messages from the message stack as a status line.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Notification
 */
class Horde_Notification_Listener_Status extends Horde_Notification_Listener
{
    /**
     * The notified message stack.
     *
     * @var array
     */
    protected $_notifiedStack = array();

    /**
     * Constructor.
     */
    public function __construct()
    {
        $image_dir = $GLOBALS['registry']->getImageDir('horde');

        $this->_handles = array(
            'horde.error' => array($image_dir . '/alerts/error.png', _("Error")),
            'horde.success' => array($image_dir . '/alerts/success.png', _("Success")),
            'horde.warning' => array($image_dir . '/alerts/warning.png', _("Warning")),
            'horde.message' => array($image_dir . '/alerts/message.png', _("Message")),
            'horde.alarm' => array($image_dir . '/alerts/alarm.png', _("Alarm"))
        );
        $this->_name = 'status';
    }

    /**
     * Outputs the status line if there are any messages on the 'status'
     * message stack.
     *
     * @param array &$messageStack  The stack of messages.
     * @param array $options        An array of options.
     * <pre>
     * 'store' - (boolean) If false, outputs message stack to page. If true,
     *                     stores the message stack for subsequent retrieval
     *                     via getStack(). DEFAULT: false
     * </pre>
     */
    public function notify(&$messageStack, $options = array())
    {
        if (!count($messageStack)) {
            return;
        }

        $store = !empty($options['store']);

        if (!$store) {
            echo '<ul class="notices">';
        }

        while ($message = array_shift($messageStack)) {
            $message = $this->getMessage($message, array('data' => $store));
            if ($store) {
                $this->_notifiedStack[] = $message;
            } else {
                echo $message;
            }
        }

        if (!$store) {
            echo '</ul>';
        }
    }

    /**
     * Returns one message.
     *
     * @param array $message  One message hash from the stack.
     * @param array $options  An array of options.
     * <pre>
     * 'data' - (boolean) If false, returns HTML code. If true, returns an
     *                    array of message information. DEFAULT: false
     * </pre>
     *
     * @return mixed  TODO
     */
    public function getMessage($message, $options = array())
    {
        $event = $this->getEvent($message);
        $flags = $this->getFlags($message);
        $result = array('type' => $message['type']);

        if ($event instanceof Horde_Notification_Event &&
            $message['type'] == 'horde.alarm') {
            if (empty($options['data'])) {
                $text = $this->_getAlarm($flags['alarm']);
            } else {
                $result['alarm'] = $flags['alarm'];
                if (!empty($result['alarm']['params']['notify']['ajax'])) {
                    $result['alarm']['ajax'] = $result['alarm']['params']['notify']['ajax'];
                } elseif (!empty($result['alarm']['params']['notify']['show'])) {
                    $result['alarm']['url'] = (string)Horde::url($GLOBALS['registry']->linkByPackage($result['alarm']['params']['notify']['show']['__app'], 'show', $result['alarm']['params']['notify']['show']), true);
                }
                unset($result['alarm']['params']['notify'],
                      $result['alarm']['methods']);
            }
        } else {
            $text = $event->getMessage();
            if (!empty($options['data'])) {
                $result['message'] = $text;
            }
            if (!in_array('content.raw', $this->getFlags($message))) {
                $text = htmlspecialchars($text, ENT_COMPAT, Horde_Nls::getCharset());
            }
        }

        return empty($options['data'])
            ? '<li>' . Horde::img($this->_handles[$message['type']][0], $this->_handles[$message['type']][1], '', '') . $text . '</li>'
            : $result;
    }

    /**
     * Renders the interface for an alarm notification.
     *
     * @param array $alarm  An alarm hash.
     *
     * @return string  The generated HTML code for the alarm notification.
     */
    protected function _getAlarm(array $alarm)
    {
        $message = htmlspecialchars($alarm['title']);

        if (!empty($alarm['params']['notify']['show'])) {
            $message = Horde::link(Horde::url($GLOBALS['registry']->linkByPackage($alarm['params']['notify']['show']['__app'], 'show', $alarm['params']['notify']['show'])), $alarm['text']) . $message . '</a>';
        }

        $browser = Horde_Browser::singleton();
        if (!empty($alarm['user']) && $browser->hasFeature('xmlhttpreq')) {
            Horde::addScriptFile('prototype.js', 'horde');
            $url = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/snooze.php', true);
            $opts = array('-1' => _("Dismiss"),
                          '5' => _("5 minutes"),
                          '15' => _("15 minutes"),
                          '60' => _("1 hour"),
                          '360' => _("6 hours"),
                          '1440' => _("1 day"));
            $id = 'snooze_' . md5($alarm['id']);
            $message .= ' <small onmouseover="if(typeof ' . $id . '_t!=\'undefined\')clearTimeout(' . $id . '_t);Element.show(\'' . $id . '\')" onmouseout="' . $id . '_t=setTimeout(function(){Element.hide(\'' . $id . '\')},500)">[' . _("Snooze...") . '<span id="' . $id . '" style="display:none"> ';
            $first = true;
            foreach ($opts as $minutes => $desc) {
                if (!$first) {
                    $message .= ', ';
                }
                $message .= Horde::link('#', '', '', '', 'new Ajax.Request(\'' . $url . '\',{parameters:{alarm:\'' . $alarm['id'] . '\',snooze:' . $minutes . '},onSuccess:function(){Element.remove(this);}.bind(this.parentNode.parentNode.parentNode)});return false;') . $desc . '</a>';
                $first = false;
            }
            $message .= '</span>]</small>';
        }

        return $message;
    }

    /**
     * Returns all status messages stored via the 'store' option to notify().
     *
     * @param boolean $clear  Clear the entries off the internal stack?
     *
     * @return array  An array of data items.
     */
    public function getStack($clear = true)
    {
        $info = $this->_notifiedStack;
        if ($clear) {
            $this->_notifiedStack = array();
        }
        return $info;
    }

}
