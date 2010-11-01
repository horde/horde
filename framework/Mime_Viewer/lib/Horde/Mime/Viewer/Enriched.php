<?php
/**
 * The Horde_Mime_Viewer_Enriched class renders out plain text from enriched
 * content tags, ala RFC 1896.
 *
 * By RFC, we must do the minimal conformance measures of: A minimal
 * text/enriched implementation is one that converts "<<" to "<",
 * removes everything between a <param> command and the next balancing
 * </param> removes all other formatting commands (all text enclosed
 * in angle brackets), and outside of <nofill> environments converts
 * any series of n CRLFs to n-1 CRLFs, and converts any lone CRLF
 * pairs to SPACE.
 *
 * We don't qualify as we don't currently track the <nofill>
 * environment, that is we do CRLF conversion even if <nofill> is
 * specified in the text, but we're close at least.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Enriched extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderReturn(
            '<html><body>' . $this->_toHTML(false) . '</body></html>',
            'text/html; charset=' . $this->_mimepart->getCharset()
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        return $this->_renderReturn(
            Horde_String::convertCharset($this->_toHTML(true), $this->_mimepart->getCharset(), 'UTF-8'),
            'text/html; charset=UTF-8'
        );
    }

    /**
     * Convert the enriched text to HTML.
     *
     * @param string $inline  Rendered inline?
     *
     * @return string  The HTML-ified version of the MIME part contents.
     */
    protected function _toHTML($inline)
    {
        $text = trim($this->_mimepart->getContents());
        if (!strlen($text)) {
            return array();
        }

        // We add space at the beginning and end of the string as it will
        // make some regular expression checks later much easier (so we
        // don't have to worry about start/end of line characters)
        $text = ' ' . $text . ' ';

        // We need to preserve << tags, so map them to ascii 1 or ascii 255
        // We make the assumption here that there would never be an ascii
        // 1 in an email, which may not be valid, but seems reasonable...
        // ascii 255 would work if for some reason you don't like ascii 1
        // ascii 0 does NOT seem to work for this, though I'm not sure why
        $text = str_replace('<<', chr(1), $text);

        // Remove any unrecognized tags in the text (via RFC minimal specs)
        // any tags we just don't want to implement can also be removed here
        // Note that this will remove any html links, but this is intended
        $implementedTags = '<param><bold><italic><underline><fixed><excerpt>' .
                           '<smaller><bigger><center><color><fontfamily>' .
                           '<flushleft><flushright><flushboth><paraindent>';
        // $unImplementedTags = '<nofill><lang>';
        $text = strip_tags($text, $implementedTags);

        // restore the << tags as < tags now...
        $text = str_replace(chr(1), '<<', $text);
        // $text = str_replace(chr(255), '<', $text);

        $replace = array(
            // Get color parameters into a more useable format.
            '/<color><param>([\da-fA-F]+),([\da-fA-F]+),([\da-fA-F]+)<\/param>/Uis' => '<color r=\1 g=\2 b=\3>',
            '/<color><param>(red|blue|green|yellow|cyan|magenta|black|white)<\/param>/Uis' => '<color n=\1>',

            // Get font family parameters into a more useable format.
            '/<fontfamily><param>(\w+)<\/param>/Uis' => '<fontfamily f=\1>',

            /* Just remove any remaining parameters -- we won't use them.
             * Any tags with parameters that we want to implement will have
             * come before this. Someday we hope to use these tags (e.g. for
             * <color><param> tags). */
            '/<param>.*<\/param>/Uis' => '',

            /* Single line breaks become spaces, double line breaks are a
             * real break. This needs to do <nofill> tracking to be compliant
             * but we don't want to deal with state at this time, so we fake
             * it some day we should rewrite this to handle <nofill>
             * correctly. */
            '/([^\n])\r\n([^\r])/' => '\1 \2',
            '/(\r\n)\r\n/' => '\1'
        );
        $text = preg_replace(array_keys($replace), array_values($replace), $text);

        // We try to protect against bad stuff here.
        $text = @htmlspecialchars($text, ENT_QUOTES, $this->_mimepart->getCharset());

        // Now convert the known tags to html. Try to remove any tag
        // parameters to stop people from trying to pull a fast one
        $replace = array(
            '/(?<!&lt;)&lt;bold.*&gt;(.*)&lt;\/bold&gt;/Uis' => '<span style="font-weight: bold">\1</span>',
            '/(?<!&lt;)&lt;italic.*&gt;(.*)&lt;\/italic&gt;/Uis' => '<span style="font-style: italic">\1</span>',
            '/(?<!&lt;)&lt;underline.*&gt;(.*)&lt;\/underline&gt;/Uis' => '<span style="text-decoration: underline">\1</span>'
        );
        $text = preg_replace(array_keys($replace), array_values($replace), $text);

        $text = preg_replace_callback('/(?<!&lt;)&lt;color r=([\da-fA-F]+) g=([\da-fA-F]+) b=([\da-fA-F]+)&gt;(.*)&lt;\/color&gt;/Uis', array($this, 'colorize'), $text);

        $replace = array(
            '/(?<!&lt;)&lt;color n=(red|blue|green|yellow|cyan|magenta|black|white)&gt;(.*)&lt;\/color&gt;/Uis' => '<span style="color: \1">\2</span>',
            '/(?<!&lt;)&lt;fontfamily&gt;(.*)&lt;\/fontfamily&gt;/Uis' => '\1',
            '/(?<!&lt;)&lt;fontfamily f=(\w+)&gt;(.*)&lt;\/fontfamily&gt;/Uis' => '<span style="font-family: \1">\2</span>',
            '/(?<!&lt;)&lt;smaller.*&gt;/Uis' => '<span style="font-size: smaller">',
            '/(?<!&lt;)&lt;\/smaller&gt;/Uis' => '</span>',
            '/(?<!&lt;)&lt;bigger.*&gt;/Uis' => '<span style="font-size: larger">',
            '/(?<!&lt;)&lt;\/bigger&gt;/Uis' => '</span>',
            '/(?<!&lt;)&lt;fixed.*&gt;(.*)&lt;\/fixed&gt;/Uis' => '<font face="fixed">\1</font>',
            '/(?<!&lt;)&lt;center.*&gt;(.*)&lt;\/center&gt;/Uis' => '<div align="center">\1</div>',
            '/(?<!&lt;)&lt;flushleft.*&gt;(.*)&lt;\/flushleft&gt;/Uis' => '<div align="left">\1</div>',
            '/(?<!&lt;)&lt;flushright.*&gt;(.*)&lt;\/flushright&gt;/Uis' => '<div align="right">\1</div>',
            '/(?<!&lt;)&lt;flushboth.*&gt;(.*)&lt;\/flushboth&gt;/Uis' => '<div align="justify">\1</div>',
            '/(?<!&lt;)&lt;paraindent.*&gt;(.*)&lt;\/paraindent&gt;/Uis' => '<blockquote>\1</blockquote>',
            '/(?<!&lt;)&lt;excerpt.*&gt;(.*)&lt;\/excerpt&gt;/Uis' => '<blockquote>\1</blockquote>'
        );
        $text = preg_replace(array_keys($replace), array_values($replace), $text);

        // Replace << with < now (from translated HTML form).
        $text = str_replace('&lt;&lt;', '&lt;', $text);

        // Now we remove the leading/trailing space we added at the
        // start.
        $text = preg_replace('/^ (.*) $/s', '\1', $text);

        // Make URLs clickable.
        $text = $this->_textFilter($text, 'linkurls');

        /* Wordwrap -- note this could impact on our above RFC compliance *IF*
         * we honored nofill tags (which we don't yet). */
        $text = str_replace(array("\t", '  ', "\n "), array('        ', ' &nbsp;', "\n&nbsp;"), $text);

        if ($text[0] == ' ') {
            $text = '&nbsp;' . substr($text, 1);
        }

        return '<p class="fixed">' . nl2br($text) . '</p>';
    }

    /**
     * TODO
     */
    public function colorize($colors)
    {
        for ($i = 1; $i < 4; $i++) {
            $colors[$i] = sprintf('%02X', round(hexdec($colors[$i]) / 255));
        }
        return '<span style="color: #' . $colors[1] . $colors[2] . $colors[3] . '">' . $colors[4] . '</span>';
    }

}
