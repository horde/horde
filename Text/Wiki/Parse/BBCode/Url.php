<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for url and mail links
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * links as defined by text surrounded by [utl] ... [/url], [mail] ... [/mail]
 * or direct in lines url or mails adresses
 * On parsing, the text itself is left in place, but the starting and ending
 * tags are replaced with tokens.
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
     *              That is some regex string, must be safe with a pattern delim '#'
     * 'refused' => which schemes are refused (usefull if 'schemes' is not an exhaustive list as by default
     * 'prefixes' => which prefixes are usable for "lazy" url as www.xxxx.yyyy... (defaulted to http://...)
     * 'url-regexp' => the regexp used to match the rest of url
     * 'inline-enable' => are inline urls enabled (default true)
     *
     * @access public
     * @var array 'config-key' => mixed config-value
     */
    var $conf = array(
        'schemes' => '[a-z][-+.a-z0-9]*',  // can be also as array('htpp', 'htpps', 'ftp')
        'refused' => array('script', 'about', 'applet', 'activex', 'chrome'),
        'prefixes' => array('www', 'ftp'),
        'url-regexp' => '(?:[^.\s/"\'<\\\#delim#]*\.)*[a-z](?:[-a-z0-9]*[a-z0-9])?\.?(?:/[^\s"<\\\#delim#]*)?',
        'inline-enable' => true
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
            '#\[url(?:(=)|])(#url#)(?(1)](.*?))\[/url]#i',
            '#([\n\r\s#delim#])(#url#)#i',
        );
/*
        array ("#\[url(?:=(.+))?](.*?)\[/url]#i", 'url'),
        array ("#\[mail(?:=(.+))?](.*?)\[/mail]#i", 'mail'),
        array ("#\[mail(?:=(.+))?](.*?)\[/mail]#i", 'mail'),
*/

     /**
     * Constructor.
     * We override the constructor to build up the regex from config
     *
     * @access public
     * @param object &$obj the base conversion handler
     * @return The parser object
     */
    function Text_Wiki_Parse_Url(&$obj)
    {
        parent::Text_Wiki_Parse($obj);

        // store the list of refused schemes
        $this->refused = $this->getConf('refused', array());
        if (is_string($this->refused)) {
            $this->refused = array($this->refused);
        }
        // convert the list of recognized schemes to a regex OR,
        $schemes = $this->getConf('schemes', '[a-z][-+.a-z0-9]*');
        $url = '(?:(' . (is_array($schemes) ? implode('|', $schemes) : $schemes) . ')://';
        // add the "lazy" prefixes if any
        $prefixes = $this->getConf('prefixes', array());
        foreach ($prefixes as $val) {
            $url .= '|' . preg_quote($val, '#') . '\.';
        }
        // the full url regexp
        $url .= ')' . $this->getConf('url-regexp',
                 '(?:[^.\s/"\'<\\\#delim#]*\.)*[a-z](?:[-a-z0-9]*[a-z0-9])?\.?(?:/[^\s"<\\\#delim#]*)?');
        // inline to disable ?
        if (!$this->getConf('inline-enable', true)) {
            unset($this->regex[1]);
        }
        // replace in the regexps
        $this->regex = str_replace( '#url#', $url, $this->regex);
        $this->regex = str_replace( '#delim#', $this->wiki->delim, $this->regex);
    }

    /**
     * Generates a replacement for the matched text.  Token options are:
     * - 'type' => ['start'|'end'] The starting or ending point of the
     * emphasized text.  The text itself is left in the source.
     *
     * @access public
     * @param array &$matches The array of matches from parse().
     * @return A pair of delimited tokens to be used as a placeholder in
     * the source text surrounding the text to be emphasized.
     */
    function process(&$matches)
    {
        if ($this->refused && isset($matches[3]) && in_array($matches[3], $this->refused)) {
            return $matches[0];
        }
        $pre = '';
        if (isset($matches[1])) {
            if ($matches[1] === '=') {
                $type = 'descr';
            } else {
                $type = 'inline';
                $pre = $matches[1];
            }
        } else {
            $type = 'inline';
        }
        // set options
        $options = array(
            'type' => $type,
            'href' => (isset($matches[3]) ? '' : 'http://') . $matches[2],
            'text' => isset($matches[4]) ? $matches[4] : $matches[2]
        );

        // tokenize
        return $pre . $this->wiki->addToken($this->rule, $options);
    }
}
?>
