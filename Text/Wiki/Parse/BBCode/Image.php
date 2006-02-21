<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * BBCode: Parses for image tags
 *
 * This class implements a Text_Wiki_Rule to find source text marked as
 * images as defined by text surrounded by [img] ... [/img]
 * The tags and the text itself is replaced with a token.
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
 * Image rule parser class for BBCode.
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
class Text_Wiki_Parse_Image extends Text_Wiki_Parse {

    /**
     * Configuration keys for this rule
     * 'schemes' => URL scheme(s) (array) recognized by this rule, default is 'http|ftp|https|ftps'
     *              That is some (array of) regex string(s), must be safe with a pattern delim '#'
     * 'extensions' => URL scheme(s) (array) recognized by this rule, default is 'jpg|jpeg|gif|png'
     *              That is some (array of) regex string(s), must be safe with a pattern delim '#'
     * 'url_regexp' => the regexp used to match the url after 'scheme://' and before '.extension'
     * 'path_regexp' => the regexp used to match the local images path before '.extension'
     *
     * @access public
     * @var array 'config-key' => mixed config-value
     */
    var $conf = array(
        'schemes' => 'http|ftp|https|ftps',  // can be also as array of regexps/strings
        'extensions' => 'jpg|jpeg|gif|png',  // can be also as array of regexps/strings
        'url_regexp' =>
         '(?:[^.\s/"\'<\\\#delim#\ca-\cz]+\.)*[a-z](?:[-a-z0-9]*[a-z0-9])?\.?(?:/[^\s"<>\\\#delim#\ca-\cz]*)?',
        'local_regexp' => '(?:/?[^/\s"<\\\#delim#\ca-\cz]+)*'
    );

     /**
     * Constructor.
     * We override the constructor to build up the regex from config
     *
     * @param object &$obj the base conversion handler
     * @return The parser object
     * @access public
     */
    function Text_Wiki_Parse_Image(&$obj)
    {
        $default = $this->conf;
        parent::Text_Wiki_Parse($obj);

        // convert the list of recognized schemes to a regex OR,
        $schemes = $this->getConf('schemes', $default['schemes']);
        $this->regex = '#\[img]((?:(?:' . (is_array($schemes) ? implode('|', $schemes) : $schemes) . ')://' .
                    $this->getConf('url_regexp', $default['url_regexp']);
        if ($local = $this->getConf('local_regexp', $default['local_regexp'])) {
            $this->regex .= '|' . ( is_array($local) ? implode('|', $local) : $local );
        }
        $this->regex .= ')';
        // add the extensions if any
        if ($extensions = $this->getConf('extensions', array())) {
            if (is_array($extensions)) {
                $extensions = implode('|', $extensions);
            }
            $this->regex .= '\.(?:' . $extensions . ')';
        }
        // replace delim in the regexps
        $this->regex = str_replace( '#delim#', $this->wiki->delim, $this->regex);
        $this->regex .= ')\[/img]#i';
    }

    /**
     * Generates a replacement token for the matched text.  Token options are:
     *     'src' => the URL / path to the image
     *     'attr' => empty for basic BBCode
     *
     * @param array &$matches The array of matches from parse().
     * @return string Delimited token representing the image
     * @access public
     */
    function process(&$matches)
    {
        // tokenize
        return $this->wiki->addToken($this->rule, array('src' => $matches[1], 'attr' => array()));
    }
}
