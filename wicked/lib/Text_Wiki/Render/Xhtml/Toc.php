<?php
/**
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Toc extends Text_Wiki_Render
{
    public $conf = array(
        'css_list' => null,
        'css_item' => null,
        'title' => '<strong>Table of Contents</strong>',
        'div_id' => 'toc',
    );

    protected $_last_level = null;

    /**
     * Renders a token into text matching the requested format.
     *
     * @access public
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        // type, id, level, count, attr.
        extract($options);

        switch ($type) {
        case 'list_start':
            Horde::addScriptFile('toc.js', 'wicked', true);

            // Add the div, class, and id.
            $html = '<div';
            $css = $this->getConf('css_list');
            if ($css) {
                $html .= " class=\"$css\"";
            }

            $div_id = $this->getConf('div_id');
            if ($div_id) {
                $html .= " id=\"$div_id\"";
            }

            // Add the title, and done.
            return $html . '>' . $this->getConf('title') . '<ol>';

        case 'list_end':
            $html = '';
            while ($this->_last_level > 1) {
                $html .= '</ol>';
                --$this->_last_level;
            }
            return $html . "\n</li></ol></div>\n\n";

        case 'item_start':
            $html = '';
            if ($this->_last_level !== null) {
                if ($level > $this->_last_level) {
                    while ($level > $this->_last_level) {
                        $html .= '<ol>';
                        ++$this->_last_level;
                    }
                    $html .= '<li';
                } elseif ($level < $this->_last_level) {
                    while ($level < $this->_last_level) {
                        $html .= '</ol>';
                        --$this->_last_level;
                    }
                    $html .= '</li><li';
                } else {
                    $html = '</li><li';
                }
            } else {
                $html = '<li';
            }
            $this->_last_level = $level;

            $css = $this->getConf('css_item');
            if ($css) {
                $html .= " class=\"$css\"";
            }

            return $html . "><a href=\"#$id\">";

        case 'item_end':
            return '</a>';
        }
    }
}
