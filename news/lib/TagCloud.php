<?php
/**
 * Extend Horde TagCloud to allow complete css font sizes
 *
 * $Id: News.php 1263 2009-02-01 23:25:56Z duck $
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license inion (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package News
 */
class News_TagCloud extends Horde_Core_Ui_TagCloud {

    /**
     * create a Element of HTML part
     *
     * @return  string a Element of Tag HTML
     * @param   array  $tag
     * @param   string $type css class of time line param
     * @param   int    $fontsize
     */
    protected function _createHTMLTag($tag, $type, $fontsize)
    {
        return sprintf("<a class=\"%s\" href=\"%s\">%s</a>\n",
                       $type,
                       $tag['url'],
                       htmlspecialchars($tag['name']));
    }

    /**
     * wrap div tag
     *
     * @return  string
     * @param   string $html
     */
    protected function _wrapDiv($html)
    {
        return $html;
    }

}
