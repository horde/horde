<?php
class Text_Wiki_Render_Doku_Image extends Text_Wiki_Render {
    
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
        if (!isset($options['attr']['align'])) {
            $options['attr']['align'] = '';
        }
        $img = '{{'.
            ($options['attr']['align'] == 'right' || $options['attr']['align'] == 'center' ? ' ' : '').
            $options['src'].
            (isset($options['attr']['width'])
             ? '?'.$options['attr']['width'].(isset($options['attr']['height'])
                                              ? 'x'.$options['attr']['height']
                                              : '')
             : '').
            ($options['attr']['align'] == 'left' || $options['attr']['align'] == 'center' ? ' ' : '').
            '}}';
        if (isset($options['attr']['link'])) {
            return '[['.$options['attr']['link'].'|'.$img.']]';
        } else {
            return $img;
        }
    }
}
?>