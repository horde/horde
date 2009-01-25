<?php

$block_name = _("Categories");

/**
 * $Id: categories.php 890 2008-09-23 09:58:23Z duck $
 *
 * @package Horde_Block
 */
class Horde_Block_News_categories extends Horde_Block {

    var $_app = 'news';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Categories");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';
        return $GLOBALS['news_cat']->getHtml();
    }
}