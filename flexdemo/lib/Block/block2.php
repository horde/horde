<?php

$block_name = _("Horde Block2");

/**
 * @package Horde_Block
 */
class Horde_Block_flexdemo_block2 extends Horde_Block {

    var $_app = 'horde';

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Block2");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        $html = '<h2>Block2</h2>';
        return $html;
    }


    function toHtml()
    {
        return $this->_content();
    }

}
