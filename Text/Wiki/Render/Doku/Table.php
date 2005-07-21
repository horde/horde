<?php

class Text_Wiki_Render_Doku_Table extends Text_Wiki_Render {
    
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
        static $lastChar = '';

        // make nice variable names (type, attr, span)
        $pad = '    ';
        
        switch ($options['type']) {
        
        case 'table_start':
            return '';
            break;
        
        case 'table_end':
            return "\n";
            break;
        
        case 'row_start':
            return "\n";
            break;
        
        case 'row_end':
            return $lastChar == '' ? '|' : $lastChar;
            break;
        
        case 'cell_start':
            // is this a TH or TD cell?
            if ($options['attr'] == 'header') {
                // start a header cell
                $output = '^ ';
            } else {
                // start a normal cell
                $output = '| ';
            }
            
            // add alignment
            switch($options['attr']) {
            case 'left':
                break;
            case 'right':
            case 'center':
                $output .= ' ';
                break;
            }

            return $output;
            break;
        
        case 'cell_end':
            if ($options['span'] > 1) {
                if ($options['attr'] == 'header') {
                    $char = '^';
                } else {
                    $char = '|';
                }
                $output = ' '.str_pad('', $options['span'] - 1, $char);
            } else {
                $output = ' ';
            }
            $lastChar = $char;
            // add alignment
            switch($options['attr']) {
            case 'left':
            case 'center':
                $output = ' '.$output;
                break;
            case 'right':
                break;
            }
            return $output;
            break;
        
        default:
            return '';
        
        }
    }
}
?>