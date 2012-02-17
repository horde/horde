<?php
/**
 * Renders a definition list for a Wiki page.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPLv2). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/gpl
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */

/**
 * Renders a definition list for a Wiki page.
 *
 * @category Horde
 * @package  Wicked
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @link     http://www.horde.org/apps/wicked
 * @license  http://www.horde.org/licenses/gpl GNU General Public License, version 2
 */
class Text_Wiki_Render_Rst_Deflist extends Text_Wiki_Render
{
    /**
     * Renders a token into text matching the requested format.
     * 
     * @param array $options The "options" portion of the token (second
     * element).
     * 
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        $type = $options['type'];
        switch ($type) {
        case 'list_start':
            $this->wiki->registerRenderCallback(array($this, 'deflist'));
            return '';
        case 'list_end':
            $this->wiki->popRenderCallback();
            return Text_Wiki_Render_Rst_Links::append();
        case 'term_end':
        case 'narr_end':
            return $this->wiki->delim;
        case 'term_start':
        case 'narr_start':
        default:
            return '';

        }
    }

    public function deflist($block)
    {
        $elements = explode($this->wiki->delim, $block);
        $term = false;
        $list = array();
        foreach ($elements as $element) {
            if ($term === false) {
                $term = $element;
            } else {
                $list[$term] = $element;
                $term = false;
            }
        }
        $term_length = max(array_map('strlen', array_keys($list)));
        $result = '';
        foreach ($list as $term => $info) {
            $lead = Horde_String::pad($term . ': ', $term_length + 2);
            $definition = Horde_String::wordwrap(
                $lead . $info,
                max(80, $term_length + 30),
                "\n" . str_repeat(' ', $term_length + 3)
            );
            $result .= ':' . $definition . "\n";
        }
        $result .= "\n";
        return $result;
    }
}
