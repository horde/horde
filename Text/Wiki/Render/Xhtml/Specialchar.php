<?php

class Text_Wiki_Render_Xhtml_SpecialChar extends Text_Wiki_Render {

    var $types = array('~bs~' => '&#92;',
                       '~hs~' => '&nbsp;',
                       '~amp~' => '&amp;',
                       '~ldq~' => '&ldquo;',
                       '~rdq~' => '&rdquo;',
                       '~lsq~' => '&lsquo;',
                       '~rsq~' => '&rsquo;',
                       '~c~' => '&copy;',
                       '~--~' => '&mdash;',
                       '" -- "' => '&mdash;',
                       '&quot; -- &quot;' => '&mdash;',
                       '~lt~' => '&lt;',
                       '~gt~' => '&gt;');
    
    function token($options)
    {
        if (isset($this->types[$options['char']])) {
            return $this->types[$options['char']];
        } else {
            return '&#'.substr($options['char'], 1, -1).';';
        }
    }
}

?>