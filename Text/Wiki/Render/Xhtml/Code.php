<?php

class Text_Wiki_Render_Xhtml_Code extends Text_Wiki_Render {
    
    
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
        $text = $options['text'];
        $attr = $options['attr'];
        
        if (strtolower($attr['type']) == 'php') {
        	
        	// PHP code example
        	
            // add the PHP tags
            $text = "<?php\n" . $options['text'] . "\n?>"; // <?php
            
            // convert tabs to four spaces
            $text = str_replace("\t", "    ", $text);
            
            // colorize the code block (also converts HTML entities and adds
            // <code>...</code> tags)
            ob_start();
            highlight_string($text);
            $text = ob_get_contents();
            ob_end_clean();
            
            // replace <br /> tags with simple newlines
            //$text = str_replace("<br />", "\n", $text);
            
            // replace non-breaking space with simple spaces
            //$text = str_replace("&nbsp;", " ", $text);
            
            // replace <br /> tags with simple newlines
            // replace non-breaking space with simple spaces
            // translate old HTML to new XHTML
            // courtesy of research by A. Kalin :-)
            $map = array(
                '<br />'  => "\n",
                '&nbsp;'  => ' ',
                '<font'   => '<span',
                '</font>' => '</span>',
                'color="' => 'style="color:'
            );
            $text = strtr($text, $map);
           
            // get rid of the last newline inside the code block
            // (becuase higlight_string puts one there)
            if (substr($text, -8) == "\n</code>") {
                $text = substr($text, 0, -8) . "</code>";
            }
            
            // done
            $text = "<pre>$text</pre>";
        
        } elseif (strtolower($attr['type']) == 'html') {
        
            // HTML code example:
            // add <html> opening and closing tags,
            // convert tabs to four spaces,
            // convert entities.
            $text = str_replace("\t", "    ", $text);
            $text = "<html>\n$text\n</html>";
            $text = htmlentities($text);
            $text = "<pre><code>$text</code></pre>";
            
        } else {
            // generic code example:
            // convert tabs to four spaces,
            // convert entities.
            $text = str_replace("\t", "    ", $text);
            $text = htmlentities($text);
            $text = "<pre><code>$text</code></pre>";
        }
        
        return "\n$text\n\n";
    }
}
?>