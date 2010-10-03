<?php
/**
 * The Horde_Block_Layout_View class represents the user defined portal layout.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Duck <duck@obala.net>
 * @package Horde_Block
 */
class Horde_Block_Layout_View_Js extends Horde_Block_Layout_View {

    /**
     * Render the current layout as HTML.
     *
     * @return string HTML layout.
     */
    function toHtml()
    {
        $html = '<div id="page">';
        $js = '<script type="text/javascript">' . "\n";
        $js .= 'var confirm_remove = "' . _("Are sure to remove this block?") . '";' . "\n";
        $js .= 'var edit_url = "' . Horde::url('dragdrop/params.php') . '";' . "\n";
        $js .= 'var load_url = "' . Horde::url('dragdrop/block.php') . '";' . "\n";
        $js .= 'var list_url = "' . Horde::url('dragdrop/select.php') . '";' . "\n";
        $js .= 'var save_url = "' . Horde::url('dragdrop/save.php') . '";' . "\n";

        $js_init = '<script type="text/javascript">'
                . ' function init() {'
                . ' portal = new Xilinus.Portal("#page div", {onOverWidget: onOverWidget, onOutWidget: onOutWidget});';

        $js_id = 0;
        $widget_col = 0;
        $columns = 0;
        foreach ($this->_layout as $row_num => $row) {
            foreach ($row as $col_num => $item) {
                if ($col_num > $columns) {
                    $columns = $col_num;
                }
                if (is_array($item)) {
                    $js .= $this->_serializeBlock($js_id, $item['app'], $item['params']['type'],
                                                $item['params']['params'], $js_init, $col_num);
                    $js_id++;
                }
            }
        }

        $columns = max($columns, 2); // FOR TESTING ADD AT KEAST 3 COLUMNS
        for ($col_num = 0; $col_num <= $columns; $col_num++) {
            $html .= '<div id="widget_col_' . $col_num . '"></div>';
        }

        $js .= '</script>';

        $js_init .= 'portal.addWidgetControls("control_buttons");'
                . '}'
                . 'document.observe("dom:loaded", init);
'
                . '</script>';

        $html .= '</div>' . "\n" . $js . "\n" . $js_init;

        // Strip any CSS <link> tags out of the returned content so
        // they can be handled seperately.
        if (preg_match_all('/<link .*?rel="stylesheet".*?\/>/', $html, $links)) {
            $html = str_replace($links[0], '', $html);
            $this->_linkTags = $links[0];
        }

        return $html;
    }

    function _serializeBlock($js_id, $app, $name, $params, &$js_init, $col_num)
    {
        $block = Horde_Block_Collection::getBlock($app, $name, $params);
        if ($block instanceof PEAR_Error) {
            $title = $block->getMessage();
            $content = $block->getDebugInfo();
            $params = array();
        } else {
            $content = @$block->getContent();
            if ($content instanceof PEAR_Error) {
                $content = $content->getDebugInfo();
            }
            $title = @$block->getTitle();
            if ($title instanceof PEAR_Error) {
                $title = $title->getMessage();
            } else {
                $title = strip_tags($title);
            }
        }

        $content = Horde_Serialize::serialize($content, Horde_Serialize::JSON);
        $title = Horde_Serialize::serialize($title, Horde_Serialize::JSON);
        $params = Horde_Serialize::serialize($params, Horde_Serialize::JSON);

        $js_init .= 'portal.add(new Xilinus.Widget().'
                    . 'setTitle(title_' . $js_id .').'
                    . ' setContent(content_' . $js_id .'), ' . $col_num . ');'
                    . '_widgets_blocks[' . $js_id . '] = "' . $app . ':' . $name . '";'
                    . '_layout_params[' . $js_id . '] = \'' . $params . '\'.evalJSON();'
                    . 'delete title_' . $js_id .';'
                    . 'delete content_' . $js_id .';' . "\n";

        return 'var content_' . $js_id . ' = ' . $content . ';' . "\n"
                . 'var title_' . $js_id . ' = ' . $title . ';' . "\n";
    }
}
