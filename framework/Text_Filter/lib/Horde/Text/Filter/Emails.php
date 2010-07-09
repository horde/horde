<?php
/**
 * The Horde_Text_Filter_Emails:: class finds email addresses in a block of
 * text and turns them into links.
 *
 * Parameters:
 * <pre>
 * always_mailto - (boolean) If true, a mailto: link is generated always.
 *                 Only if no mail/compose registry API method exists
 *                 otherwise.
 * class - (string) CSS class of the generated <a> tag.  Defaults to none.
 * encode - (boolean) Whether to escape special HTML characters in the URLs
 *          and finally "encode" the complete tag so that it can be decoded
 *          later with the decode() method. This is useful if you want to run
 *          htmlspecialchars() or similar *after* using this filter.
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
        'always_mailto' => false,
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
        $class = empty($this->_params['class'])
            ? ''
            : ' class="' . $this->_params['class'] . '"';

        $regexp = <<<EOR
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
                (^|\s|&lt;|<)([\w-+.=]+@[-A-Z0-9.]*[A-Z0-9])
                # Pattern 11 to 13: Optional parameters
                ((\?)([^\s"<]*[\w+#?\/&=]))?
                # Pattern 14: Optional closing bracket
                (>)?
            /eix
EOR;

        if (isset($GLOBALS['registry']) &&
            $GLOBALS['registry']->hasMethod('mail/compose') &&
            !$this->_params['always_mailto']) {
            /* If we have a mail/compose registry method, use it. */
            $replacement = 'Horde_Text_Filter_Emails::callback(\'registry\', \''
                . $this->_params['encode'] . '\', \'' . $class
                . '\', \'$1\', \'$2\', \'$3\', \'$4\', \'$5\', \'$7\', \'$8\', \'$9\', \'$10\', \'$11\', \'$13\', \'$14\')';
        } else {
            /* Otherwise, generate a standard mailto: and let the browser
             * handle it. */
            if ($this->_params['encode']) {
                $replacement = <<<EOP
                    '$9' === ''
                    ? htmlspecialchars('$1$2') . '<a$class href="mailto:'
                        . htmlspecialchars('$3$5') . '" title="'
                        . sprintf(_("New Message to %s"), htmlspecialchars('$3'))
                        . '">' . htmlspecialchars('$3$5') . '</a>'
                        . htmlspecialchars('$4$8')
                    : htmlspecialchars('$9') . '<a$class href="mailto:'
                        . htmlspecialchars('$10$11') . '" title="'
                        . sprintf(_("New Message to %s"), htmlspecialchars('$10'))
                        . '">' . htmlspecialchars('$10$11') . '</a>'
                        . htmlspecialchars('$14')
EOP;
                $replacement = 'chr(1).chr(1).chr(1).base64_encode(' . $replacement . ').chr(1).chr(1).chr(1)';
            } else {
                $replacement = 'Horde_Text_Filter_Emails::callback(\'link\', \''
                    . $this->_params['encode'] . '\', \'' . $class
                    . '\', \'$1\', \'$2\', \'$3\', \'$4\', \'$5\', \'$7\', \'$8\', \'$9\', \'$10\', \'$11\', \'$13\', \'$14\')';
            }
        }

        return array('regexp' => array($regexp => $replacement));
    }

    /**
     * TODO
     */
    static public function callback($mode, $encode, $class, $bracket1,
                                    $protocol, $email, $closing, $args_long,
                                    $args, $bracket2, $prefix, $email2,
                                    $args_long2, $args2, $closebracket)
    {
        if ($mode == 'link') {
            if ($email2 === '') {
                return $bracket1 . $protocol . '<a' . $class . ' href="mailto:' . $email . $args_long . '" title="' . sprintf(_("New Message to %s"), htmlspecialchars($email)) . '">' . $email . $args_long . '</a>' . $closing . $bracket2;
            }

            return (($prefix == '<') ? '&lt;' : $prefix) . '<a' . $class . ' href="mailto:' . $email2 . $args_long2 . '" title="' . sprintf(_("New Message to %s"), htmlspecialchars($email2)) . '">' . $email2 . $args_long2 . '</a>' . ($closebracket ? '&gt;' : '');
        }

        if (!empty($email2)) {
            $args = $args2;
            $email = $email2;
            $args_long = $args_long2;
        }

        parse_str($args, $extra);
        try {
            $url = $GLOBALS['registry']->call('mail/compose', array(array('to' => $email), $extra));
        } catch (Horde_Exception $e) {
            $url = 'mailto:' . urlencode($email);
        }

        $url = str_replace('&amp;', '&', $url);
        if (substr($url, 0, 11) == 'javascript:') {
            $href = '#';
            $onclick = ' onclick="' . substr($url, 11) . ';return false;"';
        } else {
            $href = $url;
            $onclick = '';
        }

        if ($encode) {
            return chr(1).chr(1).chr(1)
                . base64_encode(
                    htmlspecialchars($bracket1 . $protocol . $prefix)
                    . '<a' . $class . ' href="' . htmlspecialchars($href)
                    . '" title="' . sprintf(_("New Message to %s"),
                                            htmlspecialchars($email))
                    . '"' . $onclick . '>'
                    . htmlspecialchars($email . $args_long) . '</a>'
                    . htmlspecialchars($bracket2))
                . chr(1).chr(1).chr(1) . $closing . ($closebracket ? '>' : '');
        }

        return $bracket1 . $protocol . $prefix . '<a' . $class
            . ' href="' . $href . '" title="'
            . sprintf(_("New Message to %s"), htmlspecialchars($email))
            . '"' . $onclick . '>' . htmlspecialchars($email) . $args_long
            . '</a>' . $bracket2 . $closing . ($closebracket ? '>' : '');
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
