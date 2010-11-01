<?php
/**
 * The Horde_Text_Filter_Emails:: class finds email addresses in a block of
 * text and turns them into links.
 *
 * Parameters:
 * <pre>
 * class - (string) CSS class of the generated <a> tag.
 *         DEFAULT: ''
 * encode - (boolean) Whether to escape special HTML characters in the URLs
 *          and finally "encode" the complete tag so that it can be decoded
 *          later with the decode() method. This is useful if you want to run
 *          htmlspecialchars() or similar *after* using this filter.
 *          DEFAULT: false
 * </pre>
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Tyler Colbert <tyler@colberts.us>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Emails extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'class' => '',
        'encode' => false
    );

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $this->_regexp = <<<EOR
        /
            # Version 1: mailto: links with any valid email characters.
            # Pattern 1: Outlook parenthesizes in square brackets
            (\[\s*)?
            # Pattern 2: mailto: protocol prefix
            (mailto:\s?)
            # Pattern 3: email address
            ([^\s\?"<&]*)
            # Pattern 4: closing angle brackets?
            (&gt;)?
            # Pattern 5 to 7: Optional parameters
            ((\?)([^\s"<]*[\w+#?\/&=]))?
            # Pattern 8: Closing Outlook square bracket
            ((?(1)\s*\]))
        |
            # Version 2 Pattern 9 and 10: simple email addresses.
            (^|\s|&lt;|<|\[)([\w-+.=]+@[-A-Z0-9.]*[A-Z0-9])
            # Pattern 11 to 13: Optional parameters
            ((\?)([^\s"<]*[\w+#?\/&=]))?
            # Pattern 14: Optional closing bracket
            (>)?
        /ix
EOR;

        return array('regexp_callback' => array(
            $this->_regexp => array($this, 'regexCallback')
        ));
    }

    /**
     * Regular expression callback.
     *
     * @param array $matches  preg_replace_callback() matches. See regex above
     *                        for description of matching data.
     *
     * @return string  Replacement string.
     */
    public function regexCallback($matches)
    {
        $data = $this->_regexCallback($matches);

        if ($this->_params['encode']) {
            $data = "\01\01\01" . base64_encode($data) . "\01\01\01";
        }

        return $matches[1] . $matches[2] . (isset($matches[9]) ? $matches[9] : '') .
            $data .
            $matches[4] . $matches[8] . (isset($matches[14]) ? $matches[14] : '');
    }

    /**
     * Regular expression callback.
     *
     * @param array $matches  preg_replace_callback() matches. See regex above
     *                        for description of matching data.
     *
     * @return string  Replacement string.
     */
    protected function _regexCallback($matches)
    {
        $class = empty($this->_params['class'])
            ? ''
            : ' class="' . $this->_params['class'] . '"';
        $email = (!isset($matches[10]) || $matches[10] === '')
            ? $matches[3] . $matches[5]
            : $matches[10] . (isset($matches[11]) ? $matches[11] : '');

        return '<a' . $class . ' href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a>';
    }

    /**
     * "Decodes" the text formerly encoded by using the "encode" parameter.
     *
     * @param string $text  An encoded text.
     *
     * @return string  The decoded text.
     */
    static public function decode($text)
    {
        return preg_replace('/\01\01\01([\w=+\/]*)\01\01\01/e', 'base64_decode(\'$1\')', $text);
    }

}
