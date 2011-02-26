<?php
/**
 * The Horde_Text_Filter_Linkurls:: class turns all URLs in the text into
 * hyperlinks. The regex used is adapted from John Gruber's:
 * http://daringfireball.net/2010/07/improved_regex_for_matching_urls
 *
 * Changes:
 *   - Require at least one slash after the protocol. Horde's other filters don't
 *     expect us to match mailto: as part of these filters, so don't.
 *   - Limit the URL protocol to 20 characters to avoid PCRE problems.
 *   - Allow "+" characters in URL protocols (like svn+ssh://).
 *
 * Parameters:
 * <pre>
 * callback - (string) A callback function that the URL is passed through
 *            before being set as the href attribute.  Must be a string with
 *            the function name, the function must take the original URL as
 *            the first and only parameter.
 *            DEFAULT: No callback
 * class - (string) The CSS class of the generated links.
 *         DEFAULT: none
 * encode - (boolean)  Whether to escape special HTML characters in the URLs
 *          and finally "encode" the complete tag so that it can be decoded
 *          later with the decode() method. This is useful if you want to
 *          run htmlspecialchars() or similar *after* using this filter.
 *          DEFAULT: false
 * nofollow - (boolean) Whether to set the 'rel="nofollow"' attribute on
 *            links.
 *            DEFAULT: false
 * target - (string) The link target.
 *          DEFAULT: '_blank'
 * </pre>
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Tyler Colbert <tyler@colberts.us>
 * @author   Jan Schneider <jan@horde.org>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Linkurls extends Horde_Text_Filter_Base
{
    /**
     * Link-finding regex
     */
    public static $regex = '';

    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'callback' => null,
        'class' => '',
        'encode' => false,
        'nofollow' => false,
        'target' => '_blank',
    );

    public static function getRegex()
    {
        if (!self::$regex) { self::initializeRegex(); }
        return self::$regex;
    }

    public static function initializeRegex()
    {
        self::$regex = <<<END_OF_REGEX
(?xi)
#\b
(                           # Capture 1: entire matched URL
  (
   [a-z][\w-+]{0,19}:/{1,3}         # URL protocol and colon followed by 1-3 slashes
    |                           #   or
    www\d{0,3}[.]               # "www.", "www1.", "www2." … "www999."
    |                           #   or
    [a-z0-9.\-]+[.][a-z]{2,4}/  # looks like domain name followed by a slash
  )
  (?:                           # One or more:
    [^\s()<>]+                      # Run of non-space, non-()<>
    |                               #   or
    \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
  )+
  (?:                           # End with:
    \(([^\s()<>]+|(\([^\s()<>]+\)))*\)  # balanced parens, up to 2 levels
    |                                   #   or
    [^\s`!()\[\]{};:\'".,<>?«»“”‘’]        # not a space or one of these punct chars
  )
)
END_OF_REGEX;
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        return array('regexp_callback' => array('@' . self::getRegex() . '@' => array($this, 'callback')));
    }

    public function callback($match)
    {
        $href = $match[0];
        if (strpos($match[2], ':') === false) {
            $href = 'http://' . $href;
        }

        if ($this->_params['callback']) {
            $href = call_user_func($this->_params['callback'], $href);
        }
        $href = htmlspecialchars($href);

        $class = $this->_params['class'];
        if (!empty($class)) { $class = ' class="' . $class . '"'; }

        $target = $this->_params['target'];
        if (!empty($target)) { $target = ' target="' . $target . '"'; }

        $replacement = '<a href="' . $href . '"' .
            ($this->_params['nofollow'] ? ' rel="nofollow"' : '') .
            $target . $class .
            '>' . htmlspecialchars($match[0]) . '</a>';

        if (!empty($this->_params['noprefetch'])) {
            $replacement = '<meta http-equiv="x-dns-prefetch-control" value="off" />' .
                $replacement .
                '<meta http-equiv="x-dns-prefetch-control" value="on" />';
        }

        if ($this->_params['encode']) {
            $replacement = chr(0) . chr(0) . chr(0) . base64_encode($replacement) . chr(0) . chr(0) . chr(0);
        }

        return $replacement;
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
        return preg_replace('/\00\00\00([\w=+\/]*)\00\00\00/e', 'base64_decode(\'$1\')', $text);
    }
}
