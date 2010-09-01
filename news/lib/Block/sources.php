<?php

$block_name = _("Sources");

/**
 * $Id: sources.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
 */
class Horde_Block_News_sources extends Horde_Block {

    var $_app = 'news';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Sources");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        $sources = $GLOBALS['news']->getSources();

        $html = '';
        $url = Horde::url('browse.php');
        foreach ($sources as $source_id => $source_name) {
            $html .= '- ' 
                  . Horde::link(Horde_Util::addparameter($url, 'source_id', $source_id), '', '', '_blank')
                  . $source_name . '</a><br />';
        }

        return $html;
    }
}
