<?php
/**
 * This class defines the base Horde status notification types.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Notification_Status extends Horde_Notification_Event_Status
{
    /**
     * Constructor.
     *
     * @param mixed $data   Message: either a string or an Exception or
     *                      PEAR_Error object.
     * @param string $type  The event type.
     * @param array $flags  The flag array.
     */
    public function __construct($data, $type = null, array $flags = array())
    {
        if (empty($type)) {
            $type = ($data instanceof PEAR_Error || $data instanceof Exception)
                ? 'horde.error'
                : (is_string($data) ? 'horde.message' : 'horde.error');
        }

        $this->charset = $GLOBALS['registry']->getCharset();

        parent::__construct($data, $type, $flags);
    }

    /**
     * String representation of this object.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        $text = null;

        switch ($this->type) {
        case 'horde.alarm':
            $alarm = $this->flags['alarm'];
            $text = $alarm['title'];

            if (!empty($alarm['params']['notify']['show'])) {
                try {
                    $text = Horde::link(Horde::url($GLOBALS['registry']->linkByPackage($alarm['params']['notify']['show']['__app'], 'show', $alarm['params']['notify']['show'])), $alarm['text']) . $text . '</a>';
                } catch (Horde_Exception $e) {
                    return $e->getMessage();
                }
            }

            if (!empty($alarm['user']) &&
                $GLOBALS['browser']->hasFeature('xmlhttpreq')) {
                Horde::addScriptFile('prototype.js', 'horde');
                try {
                    $url = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/snooze.php', true);
                } catch (Horde_Exception $e) {
                    return $e->getMessage();
                }
                $opts = array(
                    '-1' => _("Dismiss"),
                    '5' => _("5 minutes"),
                    '15' => _("15 minutes"),
                    '60' => _("1 hour"),
                    '360' => _("6 hours"),
                    '1440' => _("1 day")
                );
                $id = 'snooze_' . md5($alarm['id']);
                $text .= ' <small onmouseover="if(typeof ' . $id . '_t!=\'undefined\')clearTimeout(' . $id . '_t);Element.show(\'' . $id . '\')" onmouseout="' . $id . '_t=setTimeout(function(){Element.hide(\'' . $id . '\')},500)">[' . _("Snooze...") . '<span id="' . $id . '" style="display:none"> ';
                $first = true;
                foreach ($opts as $minutes => $desc) {
                    if (!$first) {
                        $text .= ', ';
                    }
                    $text .= Horde::link('#', '', '', '', 'new Ajax.Request(\'' . $url . '\',{parameters:{alarm:\'' . $alarm['id'] . '\',snooze:' . $minutes . '},onSuccess:function(){Element.remove(this);}.bind(this.parentNode.parentNode.parentNode)});return false;') . $desc . '</a>';
                    $first = false;
                }
                $text .= '</span>]</small>';
            }

            $img = 'alerts/alarm.png';
            $label = _("Alarm");
            break;

        case 'horde.error':
            $img = 'alerts/error.png';
            $label = _("Error");
            $text = parent::__toString();
            break;

        case 'horde.message':
            $img = 'alerts/message.png';
            $label = _("Message");
            $text = parent::__toString();
            break;

        case 'horde.success':
            $img = 'alerts/success.png';
            $label = _("Success");
            $text = parent::__toString();
            break;

        case 'horde.warning':
            $img = 'alerts/warning.png';
            $label = _("Warning");
            $text = parent::__toString();
            break;

        default:
            return parent::__toString();
        }

        return Horde::img($img, $label) . $text;
    }

}
