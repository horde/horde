<?php
/**
 * The Horde_Mime_Viewer_Php class renders out syntax-highlighted PHP code in
 * HTML format.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Php extends Horde_Mime_Viewer_Source
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => false,
        'full' => true,
        'info' => false,
        'inline' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $ret = $this->_renderInline();

        // Need Horde headers for CSS tags.
        reset($ret);
        $ret[key($ret)]['data'] =  Horde_Util::bufferOutput('require', $GLOBALS['registry']->get('templates', 'horde') . '/common-header.inc') .
            $ret[key($ret)]['data'] .
            Horde_Util::bufferOutput('require', $GLOBALS['registry']->get('templates', 'horde') . '/common-footer.inc');

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        ini_set('highlight.comment', 'comment');
        ini_set('highlight.default', 'default');
        ini_set('highlight.keyword', 'keyword');
        ini_set('highlight.string', 'string');
        ini_set('highlight.html', 'html');

        $code = $this->_mimepart->getContents();
        $text = (strpos($code, '<?php') === false)
            ? $this->_lineNumber(str_replace('&lt;?php&nbsp;', '', highlight_string('<?php ' . $code, true)))
            : $this->_lineNumber(highlight_string($code, true));

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $text,
                'status' => array(),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
    }

    /**
     * Add line numbers to a block of code.
     *
     * @param string $code  The code to number.
     *
     * @return string  The code with line numbers added.
     */
    protected function _lineNumber($code)
    {
        // Clean up.
        $code = preg_replace(
            array(
                '/<code><span style="color: #?\w+">\s*/',
                '/<code><font color="#?\w+">\s*/',
                '/\s*<\/span>\s*<\/span>\s*<\/code>/',
                '/\s*<\/font>\s*<\/font>\s*<\/code>/'
            ), '', $code);

        $code = str_replace(
            array('&nbsp;', '&amp;', '<br />', '<span style="color: ', '<font color="', '</font>',),
            array(' ', '&#38;', "\n", '<span class="', '<span class="', '</span>',),
            $code);

        $code = trim($code);

        // Normalize newlines.
        $code = str_replace("\r", '', $code);
        $code = preg_replace('/\n\n\n+/', "\n\n", $code);

        $results = array('<ol class="code-listing striped">');
        $previous = false;

        $lines = explode("\n", $code);
        reset($lines);
        while (list($lineno, $line) = each($lines)) {
            if (substr($line, 0, 7) == '</span>') {
                $previous = false;
                $line = substr($line, 7);
            }

            if (empty($line)) {
                $line = '&#160;';
            }

            if ($previous) {
                $line = "<span class=\"$previous\">" . $line;
            }

            // Save the previous style.
            if (strpos($line, '<span') !== false) {
                switch (substr($line, strrpos($line, '<span') + 13, 1)) {
                case 'c':
                    $previous = 'comment';
                    break;

                case 'd':
                    $previous = 'default';
                    break;

                case 'k':
                    $previous = 'keyword';
                    break;

                case 's':
                    $previous = 'string';
                    break;
                }
            }

            // Unset previous style unless the span continues.
            if (substr($line, -7) == '</span>') {
                $previous = false;
            } elseif ($previous) {
                $line .= '</span>';
            }

            $results[] = '<li id="l' . ($lineno + 1). '">' . $line . '</li>';
        }

        $results[] = '</ol>';

        return implode("\n", $results);
    }

}
