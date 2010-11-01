<?php
/**
 * The Horde_Text_Filter_Bbcode:: class finds bbcode-style markup (see below)
 * in a block of text and turns it into HTML.
 *
 * Parameters:
 * <pre>
 * entities - (boolean) Before replacing bbcode with HTML tags, replace HTML
 *            entities?
 *            DEFAULT: false
 * </pre>
 *
 * Supported bbcode:
 * <pre>
 *     [b]Bold Text[/b]
 *     [i]Italics Text[/i]
 *     [u]Underlined Text[/u]
 *     [quote]Quoted Text[/quote]
 *     [center]Centered Text[/center]
 *
 *     List of items
 *     [list]
 *     [*] Item one
 *     [*] Item two
 *     [/list]
 *
 *     Numbered list
 *     [numlist]
 *     [*] Item one
 *     [*] Item two
 *     [/numlist]
 *
 *     [url]http://www.horde.org[/url] -> Link to the address using the
 *         address itself for the text.  You can specify the protocol: http or
 *         https and the port.
 *     [url]www.horde.org[/url] -> Link to the address using the address
 *         itself for the text.  You can specify the port.  The protocol is by
 *         default http.
 *     [url=http://www.horde.org]Link to Horde[/url] -> Link to the address
 *         using "Link to Horde" for the text.  You can specify the protocol:
 *         http or https and the port.
 *     [url=www.horde.org]Link to Horde[/url] -> Link to the address using
 *         "Link to Horde" for the text.  You can specify the port.  The
 *         protocol is by default http
 *     [email]cpedrinaci@yahoo.es[/email] -> sets a mailto link.
 *     [email=cpedrinaci@yahoo.es]Mail to Carlos[/email] -> Sets a mailto link
 *         and the text is "Mail to Carlos".
 * </pre>
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * Email validation based on Chuck Hagenbuch's
 * Mail_RFC822::isValidInetAddress().
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Carlos Pedrinaci <cpedrinaci@yahoo.es>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Text_Filter
 */
class Horde_Text_Filter_Bbcode extends Horde_Text_Filter_Base
{
    /**
     * Filter parameters.
     *
     * @var array
     */
    protected $_params = array(
        'entities' => false
    );

    /**
     * Executes any code necessary before applying the filter patterns.
     *
     * @param string $text  The text before the filtering.
     *
     * @return string  The modified text.
     */
    public function preProcess($text)
    {
        if ($this->_params['entities']) {
            $text = @htmlspecialchars($text);
        }

        return $text;
    }

    /**
     * Returns a hash with replace patterns.
     *
     * @return array  Patterns hash.
     */
    public function getPatterns()
    {
        $replace = array(
            '[i]' => '<em>', '[/i]' => '</em>',
            '[u]' => '<u>', '[/u]' => '</u>',
            '[b]' => '<strong>', '[/b]' => '</strong>',
            '[s]' => '<strike>', '[/s]' => '</strike>',
            '[sub]' => '<sub>', '[/sub]' => '</sub>',
            '[sup]' => '<sup>', '[/sup]' => '</sup>',
            '[center]' => '<center>', '[/center]' => '</center>',
            '[quote]' => '<blockquote>', '[/quote]' => '</blockquote>',
            '[list]' => '<ul>', '[/list]' => '</ul>',
            '[numlist]' => '<ol>', '[/numlist]' => '</ol>',
            '[*]' => '<li>'
        );

        /* When checking URLs we validate part of them, but it is up
         * to the user to write them correctly (in particular the
         * query string). Concerning mails we use the regular
         * expression in Mail_RFC822's isValidInetAddress() function,
         * slightly modified. */
        $regexp = array(
            "#\[url\]((http|https)://([a-zA-Z\d][\w-]*)(\.[a-zA-Z\d][\w-]*)+(:(\d+))?(/([^<>]+))*)\[/url\]#U" => $this->_link("$1", "$1") . "$1</a>",

            "#\[url\=((http|https)://([a-zA-Z\d][\w-]*)(\.[a-zA-Z\d][\w-]*)+(:(\d+))?(/([^<>]+))*)\]([^<>]+)\[/url\]#U" => $this->_link("$1", "$1") . "$9</a>",

            "#\[url\](([a-zA-Z\d][\w-]*)(\.[a-zA-Z\d][\w-]*)+(:(\d+))?(/([^<>]+))*)\[/url\]#U" => $this->_link("http://$1", "http://$1") . "$1</a>",

            "#\[url\=(([a-zA-Z\d][\w-]*)(\.[a-zA-Z\d][\w-]*)+(:(\d+))?(/([^<>]+))*)\]([^<>]+)\[/url\]#U" => $this->_link("http://$1", "http://$1") . "$8</a>",

            "#\[email\](([*+!.&\#$|\'\\%\/0-9a-zA-Z^_`{}=?~:-]+)@(([0-9a-zA-Z-]+\.)+[0-9a-zA-Z]{2,4}))\[/email\]#U" => $this->_link("mailto:$1", "mailto:$1") . "$1</a>",

            "#\[email\=(([*+!.&\#$|\'\\%\/0-9a-zA-Z^_`{}=?~:-]+)@(([0-9a-zA-Z-]+\.)+[0-9a-zA-Z]{2,4}))\]([^<>]+)\[/email\]#U" => $this->_link("mailto:$1", "mailto:$1") . "$5</a>",

            "#\[img\](.*)\[/img\]#U" => "<img src=\"$1\" alt=\"$1\" />",

            "#\[img\=(.*)\](.*)\[/img\]#U" => "<img src=\"$1\" alt=\"$2\" title=\"$2\" />",

            "#\[color\=(.*)\](.*)\[/color\]#U" => "<span style=\"color: $1;\">$2</span>"
        );

        return array(
            'regexp' => $regexp,
            'replace' => $replace
        );
    }

    /**
     * Return link for use in getPatterns() regexp.
     *
     * @var string $url    The URL.
     * @var string $title  The link title.
     *
     * @return string  The opening <a> tag.
     */
    protected function _link($url, $title)
    {
        return '<a href="' . $url . '" title="' . $title . '">';
    }

}
