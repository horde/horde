<?php

require_once 'Text/Wiki/Render/Xhtml/Code.php';

/**
 * $Horde: wicked/lib/Text_Wiki/Render/Xhtml/Code2.php,v 1.7 2007/09/17 17:31:03 jan Exp $
 *
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Code2 extends Text_Wiki_Render_Xhtml_Code {

    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     */
    function token($options)
    {
        $type = $options['attr']['type'];

        if ($type == 'php' || $type == 'htmlphp') {
            $search1 = '|<pre><code><span style="color: ?#000000">\n<span style="color: ?#0000BB">&lt;\?php\n\n?|';
            $replace1 = '<pre><code><span style="color:#0000BB">';
            $search2 = '|<span style="color: ?#0000BB">\?&gt;</span>\n</span></code></pre>|';

            if ($type == 'htmlphp') {
                $options['attr']['type'] = 'php';
                $options['text'] = "\n?>" . $options['text'] . "<?php\n";
                $search1 = substr($search1, 0, -1) . '\?&gt;\n?</span>|';
                $replace1 = '<pre><code>';
                $search2 = '|<span style="color: ?#0000BB">&lt;\?php\n\n\?&gt;</span>\n</span></code></pre>|';
            }
        } else {
            $search1 = '|<pre><code>\n|';
            $replace1 = "<pre><code>";
            $search2 = '|</code></pre>|';
        }

        $text = parent::token($options);

        $text = preg_replace(array($search1, $search2),
                             array($replace1, '</span></code></pre>'),
                             $text);

        return $text;
    }

}
