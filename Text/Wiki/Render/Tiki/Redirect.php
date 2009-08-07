<?php

/**
* 
* Render for wiki redirects.
* 
* @category Text
* 
* @package Text_Wiki
* 
* @author Rodrigo Sampaio Primo <rodrigo@utopia.org.br>
* 
* @license LGPL
* 
*/

/**
* 
* Render for wiki redirects.
* 
* This class implements a Text_Wiki_Render to output text marked to
* be a wiki redirect.
*
* @category Text
* 
* @package Text_Wiki
* 
* @author Rodrigo Sampaio Primo <rodrigo@utopia.org.br>
* 
*/

class Text_Wiki_Render_Tiki_Redirect extends Text_Wiki_Render {
    function token($options)
    {
        if ($options['type'] == 'end') {
            return '")/}';
        } else if ($options['type'] == 'start') {
            return '{REDIRECT(pageName="';
        }
    }
}
?>
