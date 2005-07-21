<?php

class Text_Wiki_Render_Tiki_Table extends Text_Wiki_Render {
    
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
        static $last = '';

        // make nice variable names (type, attr, span)
        $pad = '    ';
        
        switch ($options['type']) {
        
        case 'table_start':
            $output = '||';
            break;
        
        case 'table_end':
            $output = '||';
            break;
        
        case 'row_start':
            if ($last == 'table_start') {
                $output = '';
            } else {
                $output = "\n";
            }
            break;
        
        case 'row_end':
            $output = '';
            break;
        
        case 'cell_start':
            if ($last == 'cell_end') {
                $output = ' | ';
            } else {
                $output = '';
            }
            break;
        
        case 'cell_end':
            if ($options['span'] > 1) {
                $output = str_pad('', ($options['span'] - 1) * 3, ' | ');
            } else {
                $output = '';
            }
            break;
        
        default:
            return '';
        
        }
        $last = $options['type'];
        return $output;
    }
}
?>