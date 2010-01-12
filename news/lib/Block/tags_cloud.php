<?php

$block_name = _("Tags");

/**
 * oscar_tags_cloud:: Implementation of the Horde_Block API to show a tag
 * cloud.
 *
 * $Id: tags_cloud.php 78 2007-12-19 22:44:56Z jan $
 *
 * @package Horde_Block
 */
class Horde_Block_news_tags_cloud extends Horde_Block {

    var $_app = 'news';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Tags");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        return $GLOBALS['news']->getCloud(true);
    }
}