<?php
/**
 * Class that extends the base emoticons class to allow output of Horde image
 * tags.
 *
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Core
 */
class Horde_Core_Text_Filter_Emoticons extends Horde_Text_Filter_Emoticons
{
    /**
     * Return the HTML image tag needed to display an emoticon.
     *
     * @param string $icon  The emoticon name.
     *
     * @return string  The HTML image code.
     */
    public function getIcon($icon)
    {
        return Horde_Themes_Image::tag('emoticons/' . $this->getIcons($icon) . '.png', array(
            'alt' => $icon,
            'attr' => array(
                'align' => 'middle',
                'title' => $icon
            )
        ));
    }

}
