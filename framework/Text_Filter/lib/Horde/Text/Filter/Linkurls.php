<?php
/**
 * The Horde_Text_Filter_Linkurls:: class turns all URLs in the text into
 * hyperlinks.
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
class Horde_Text_Filter_Linkurls extends Horde_Text_Filter_Base
{
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
        'target' => '_blank'
    );

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $class = $this->_params['class'];
        if (!empty($class)) {
            $class = ' class="' . $class . '"';
        }

        $href = $this->_params['callback']
            ? '\' . htmlspecialchars(' . $this->_params['callback'] . '(\'$0\')) . \''
            : '\' . htmlspecialchars(\'$0\') . \'';

        $replacement = '<a href="' . $href . '"' .
            ($this->_params['nofollow'] ? ' rel="nofollow"' : '') .
            ' target="_blank"' . $class .
            '>\' . htmlspecialchars(\'$0\') . \'</a>';

        if (!empty($this->_params['noprefetch'])) {
            $replacement = '<meta http-equiv="x-dns-prefetch-control" value="off" />' .
                $replacement .
                '<meta http-equiv="x-dns-prefetch-control" value="on" />';
        }

        $replacement = $this->_params['encode']
            ? 'chr(0) . chr(0) . chr(0) . base64_encode(\'' . $replacement . '\') . chr(0) . chr(0) . chr(0)'
            : '\'' . $replacement . '\'';

        return array(
            'regexp' => array(
                '|([a-zA-Z0-9][\w+-]{0,19})://([^\s"<]*[\w+#?/&=])|e' => $replacement
            )
        );
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
