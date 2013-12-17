<?php
/**
 * This class defines the base IMP status notification types.
 *
 * Copyright 2010-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Notification_Event_Status extends Horde_Core_Notification_Event_Status
{
    /**
     * String representation of this object.
     *
     * @return string  String representation.
     */
    public function __toString()
    {
        switch ($this->type) {
        case 'imp.forward':
            $img = 'forwarded.png';
            $label = _("Forward");
            break;

        case 'imp.redirect':
            $img = 'forwarded.png';
            $label = _("Redirect");
            break;

        case 'imp.reply':
        case 'imp.reply_all':
        case 'imp.reply_list':
            $img = 'answered.png';
            $label = _("Reply");
            break;

        default:
            return parent::__toString();
        }

        return Horde_Themes_Image::tag(
            Horde_Themes::img($img, 'imp'),
            array('alt' => $label)
        ) . parent::__toString();
    }

}
