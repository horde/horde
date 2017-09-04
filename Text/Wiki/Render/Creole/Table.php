<?php

class Text_Wiki_Render_Creole_Table extends Text_Wiki_Render {

    /**
    *
    * Renders a token into text matching the requested format.
    *
    * @access public
    *
    * @param array $options The "options" portion of the token (second
    * element).
    *
    * @return string The text rendered from the token options.
    *
    */

    function token($options)
    {


        switch ($options['type']) {

        case 'table_start':
            return '';
            break;

        case 'table_end':
            return "\n";
            break;

        case 'caption_start':
            return '|= ';
            break;

        case 'caption_end':
            return "\n";
            break;

        case 'row_start':
            return '';
            break;

        case 'row_end':
            return "\n";
            break;

        case 'cell_start':
            // is this a TH or TD cell?
            if ($options['attr'] == 'header') {
                // start a header cell
                $output = '|= ';
            } else {
                // start a normal cell
                $output = '| ';
            }
            return $output;
            break;

        case 'cell_end':
            return ' ';
            break;

        default:
            return '';

        }
    }
}
?>