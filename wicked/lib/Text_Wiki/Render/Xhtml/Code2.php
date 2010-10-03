<?php
/**
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Code2 extends Text_Wiki_Render_Xhtml_Code
{
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

        $part = new Horde_Mime_Part();
        $part->setContents($options['text']);
        $part->setType("x-extension/$type");

        $viewer = new Horde_Core_Mime_Viewer_Syntaxhighlighter($part);
        $data = $viewer->render('inline');
        $data = reset($data);
        return $data['data'];
    }
}
