<?php
/**
 * extend Horde TagCloud to allow complete css font sizes
 */
class News_TagCloud extends Horde_UI_TagCloud {

    /**
     * create a Element of HTML part
     *
     * @return  string a Element of Tag HTML
     * @param   array  $tag
     * @param   string $type css class of time line param
     * @param   int    $fontsize
     * @access  private
     */
    function _createHTMLTag($tag, $type, $fontsize)
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
     * @access  private
     */
    function _wrapDiv($html)
    {
        return $html;
    }

}
