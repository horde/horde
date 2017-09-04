<?php
/**
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Url extends Text_Wiki_Render
{
    public $conf = array(
        'target' => '_blank'
    );

    /**
     * Renders a token into text matching the requested format.
     *
     * @param array $options  The "options" portion of the token (second
     *                        element).
     *
     * @return string  The text rendered from the token options.
     */
    public function token($options)
    {
        // Create local variables from the options array (text, href,
        // type).
        extract($options);

        // Find the rightmost dot and determine the filename
        // extension.
        $pos = strrpos($href, '.');
        $ext = Horde_String::lower(substr($href, $pos + 1));
        $href = htmlspecialchars($href);

        // Allow for alternative targets on non-anchor HREFs.
        if ($href[0] == '#') {
            $target = '';
        } else {
            $target = $this->getConf('target', '');
        }

        $output = Horde::link(Horde::externalUrl($href), $href, 'external', htmlspecialchars($target)) . htmlspecialchars($text) . '</a>';

        // Make numbered references look like footnotes.
        if ($type == 'footnote') {
            $output = '<sup>' . $output . '</sup>';
        }

        return $output;
    }
}
