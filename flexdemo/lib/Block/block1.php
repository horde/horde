<?php

$block_name = _("Horde Block1");

/**
 * @package Horde_Block
 */
class Horde_Block_flexdemo_block1 extends Horde_Block {

    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Block1");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        $html = '<h2>Block1</h2><p>foo</p>';
        return $html;
    }


    function toHtml()
    {
        return $this->_content();
    }

}
