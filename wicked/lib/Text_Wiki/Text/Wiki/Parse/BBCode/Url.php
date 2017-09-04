<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for url and mail links
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * links as defined by text surrounded by [url] ... [/url], [mail] ... [/mail]
 * described as [url=..., [mail= or direct in lines url or mails adresses
 * The eventual tags and the text itself is replaced with a token.
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * Url rule parser class for BBCode.
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  2005 bertrand Gugger
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 * @see        Text_Wiki_Parse::Text_Wiki_Parse()
 */
class Text_Wiki_Parse_Url extends Text_Wiki_Parse {

    /**
     * Configuration keys for this rule
     * 'schemes' => URL scheme(s) (array) recognized by this rule, default is the single rfc2396 pattern
     *              That is some (array of) regex string(s), must be safe with a pattern delim '#'
     * 'refused' => which schemes are refused (usefull if 'schemes' is not an exhaustive list as by default
     * 'prefixes' => which prefixes are usable for "lazy" url as www.xxxx.yyyy... (defaulted to http://...)
     * 'host_regexp' => the regexp used to match the host part of url (after 'scheme://')
     * 'path_regexp' => the regexp used to match the rest of url (starting with '/' included)
     * 'user_regexp' => the regexp used to match user name in email
     * 'inline_enable' => are inline urls and emails enabled (default true)
     *
     * @var array 'config-key' => mixed config-value
     * @access public
     */
    var $conf = array(
        'schemes' => '[a-z][-+.a-z0-9]*',  // can be also as array('htpp', 'htpps', 'ftp')
        'refused' => array('script', 'about', 'applet', 'activex', 'chrome'),
        'prefixes' => array('www', 'ftp'),
        'host_regexp' => '(?:[^.\s/"\'<\\\#delim#\ca-\cz]+\.)*[a-z](?:[-a-z0-9]*[a-z0-9])?\.?',
        'path_regexp' => '(?:/[^][\'\s"<\\\#delim#\ca-\cz]*)?',
        'user_regexp' => '[^]()<>[:;@\,."\s\\\#delim#\ca-\cz]+(?:\.[^]()<>[:;@\,."\s\\\#delim#\ca-\cz]+)*',
        'inline_enable' => true,
        'relative_enable' => false
    );

    /**
     * The regular expressions used to parse the source text and find
     * matches conforming to this rule.  Used by the parse() method.
     *
     * @access public
     * @var string
     * @see parse()
     */
    var $regex =  array(
            '#\[url(?:(=)|])(#url#)(?(1)](.*?))\[/url]#mi',
            '#([\n\r\s#delim#])(#url#)#i',
            '#\[(email)(?:(=)|])(#email#)(?(2)](.*?))\[/email]#mi',
            '#([\n\r\s#delim#](mailto:)?)(#email#)#i',
        );

     /**
     * Constructor.
     * We override the constructor to build up the regex from config
     *
     * @param object &$obj the base conversion handler
     * @return The parser object
     * @access public
     */
    function Text_Wiki_Parse_Url(&$obj)
    {
        $default = $this->conf;
        parent::Text_Wiki_Parse($obj);

        // store the list of refused schemes
        $this->refused = $this->getConf('refused', array());
        if (is_string($this->refused)) {
            $this->refused = array($this->refused);
        }
        // convert the list of recognized schemes to a regex OR,
        $schemes = $this->getConf('schemes', $default['schemes']);
        $url = '(?:(' . (is_array($schemes) ? implode('|', $schemes) : $schemes) . ')://';
        // add the "lazy" prefixes if any
        $prefixes = $this->getConf('prefixes', array());
        foreach ($prefixes as $val) {
            $url .= '|' . preg_quote($val, '#') . '\.';
        }
        $host = $this->getConf('host_regexp', $default['host_regexp']);
        $path = $this->getConf('path_regexp', $default['path_regexp']);
        // the full url regexp
        $url .= ')' . $host . $path;
        // the full email regexp
        $email = $this->getConf('user_regexp', $default['user_regexp']) . '@' . $host;
        // inline to disable ?
        if (!$this->getConf('inline_enable', true)) {
            unset($this->regex[1]);
            unset($this->regex[3]);
        }
        // relative url to enable ?
        if ($this->getConf('relative_enable', false)) {
            $this->regex[5] = str_replace( '#url#', $path, $this->regex[0]);
        }
        // replace in the regexps
        $this->regex = str_replace( '#url#', $url, $this->regex);
        $this->regex = str_replace( '#email#', $email, $this->regex);
        $this->regex = str_replace( '#delim#', $this->wiki->delim, $this->regex);
    }

    /**
     * Generates a replacement for the matched text.  Token options are:
     *     'type' => ['inline'|'footnote'|'descr'] the type of URL
     *     'href' => the URL link href portion
     *     'text' => the displayed text of the URL link
     *
     * @param array &$matches The array of matches from parse().
     * @return string Delimited token representing the url
     * @access public
     */
    function process(&$matches)
    {
        if ($this->refused && isset($matches[3]) && in_array($matches[3], $this->refused)) {
            return $matches[0];
        }
        $pre = '';
        $type = 'inline';
        if (isset($matches[1])) {
            if (strpos(strtolower($matches[1]), 'mail')) {
                if (isset($matches[2])) {
                    if ($matches[2] === '=') {
                        $type = 'descr';
                    } elseif ($matches[2]) {
                        $pre = $matches[1]{0};
                    }
                }
                $matches[2] = 'mailto:' . $matches[3];
                if (!isset($matches[4])) {
                    $matches[4] = $matches[3];
                }
            } elseif ($matches[1] === '=') {
                $type = 'descr';
            } else {
                $pre = $matches[1];
                if (!$matches[2]) {
                    $matches[2] = 'mailto:' . $matches[3];
                    $matches[4] = $matches[3];
                }
            }
        }
        // set options
        $href = (isset($matches[3]) ? '' : 'http://') . $matches[2];
        $text = isset($matches[4]) ? $matches[4] : $matches[2];

        // tokenize
        if ($type == 'inline') {
            return $pre . $this->wiki->addToken($this->rule, array(
                'type' => $type,
                'href' => $href,
                'text' => $text));
        }
        return $pre .
            $this->wiki->addToken($this->rule, array(
                'type' => 'start',
                'href' => $href,
                'text' => '')) .
            $text .
            $this->wiki->addToken($this->rule, array(
                'type' => 'end',
                'href' => $href,
                'text' => ''));
    }
}
