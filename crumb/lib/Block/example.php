<?php

$block_name = _("Example Block");

/**
 * $Horde: skeleton/lib/Block/example.php,v 1.2 2007/04/17 15:16:33 jan Exp $
 *
 * @package Horde_Block
 */
class Horde_Block_Skeleton_example extends Horde_Block {

    var $_app = 'skeleton';

    function _params()
    {
        return array('color' => array('type' => 'text',
                                      'name' => _("Color"),
                                      'default' => '#ff0000'));
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    function _title()
    {
        return _("Color");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    function _content()
    {
        $html  = '<table width="100" height="100" bgcolor="%s">';
        $html .= '<tr><td>&nbsp;</td></tr>';
        $html .= '</table>';

        return sprintf($html, $this->_params['color']);
    }

}
