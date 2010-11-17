<?php
/**
 * Class that extends the base emoticons class to allow output of Horde image
 * tags.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
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
        return Horde::img('emoticons/' . $this->getIcons($icon) . '.png', $icon, array('align' => 'middle', 'title' => $icon));
    }

}
