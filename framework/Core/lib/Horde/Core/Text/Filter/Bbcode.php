<?php
/**
 * Class that extends the base Bbcode class to allow output of Horde urls.
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
class Horde_Core_Text_Filter_Bbcode extends Horde_Text_Filter_Bbcode
{
    /**
     * Return link for use in getPatterns() regexp.
     *
     * @param string $url    The URL.
     * @param string $title  The link title.
     *
     * @return string  The opening <a> tag.
     */
    protected function _link($url, $title)
    {
        return Horde::link($url, $title);
    }

}
