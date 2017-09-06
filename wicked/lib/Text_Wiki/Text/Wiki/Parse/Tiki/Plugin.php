<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * Plugin rule end parser for tikiwiki
 *
 * PHP versions 4 and 5
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @author     Paul M. Jones <pmjones@php.net>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/Text_Wiki
 */

/**
 * This class parses plugins for tikiwiki and replace them with the produced markup
 * Plugins are defined by {NAME(param=>val, ...)}...data...{NAME} (parameters optional)
 * They can nest and produce plugins themselves
 *
 * @category   Text
 * @package    Text_Wiki
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @author     Justin Patrin <papercrane@reversefold.com>
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Text_Wiki
 */
class Text_Wiki_Parse_Plugin extends Text_Wiki_Parse {

    /**
     * Configurations keys, the script's name of the plugin will be prefixed and an extension added
     * the path is a comma separated list of directories where to find it
     *
     * @access public
     * @var string
     */
    var $conf = array (
        'file_prefix' => 'wikiplugin_',
        'file_extension' => '.php',
        'file_path' => 'lib/wiki-plugins/'
    );

    /**
     * The regular expression used in second stage to find plugin's arguments
     *
     * @access public
     * @var string
     */
    var $regexArgs = '#(\w+?)=(?:>|&gt;)?([^"\'][^,]+?|"[^"]+"|\'[^\']+\')#';

    /**
    *
    * The regular expression used to find source text matching this
    * rule.
    *
    * @access public
    *
    * @var string
    *
    */

    // var $regex = '/\{([A-Z]+?)\((.*?)\)}((?:(?R)|.)*?)\{\1}/msi';
    var $regex = '#(?:\{([A-Z]+?)\((.*?)\)}|(~pp~)|(~np~)|(&lt;pre&gt;))
                  ((?:(?R)|.)*?)
                  (?(1)\{\1})(?(3)~/pp~)(?(4)~/np~)(?(5)&lt;/pre&gt;)#mixs';

    /**
    *
    * Generates a token entry for the matched text.  Token options are:
    *
    * 'text' => The full matched text, not including the <code></code> tags.
    *
    * @access public
    *
    * @param array &$matches The array of matches from parse().
    *
    * @return A delimited token number to be used as a placeholder in
    * the source text.
    *
    */

    function process(&$matches)
    {
        // preparsed area
        if (isset($matches[3]) || isset($matches[5])) {
            return $this->wiki->addToken('Preformatted',
                                         array('text' => $matches[6]));
        }
        // non parsed area
        if (isset($matches[4])) {
            return $this->wiki->addToken('Raw',
                                         array('text' => $matches[6]));
        }
        // plugin
        $func = $this->getConf('file_prefix') . strtolower($matches[1]);
        if (!function_exists($func)) {
            $file = $func . $this->getConf('file_extension');
            $paths = explode(',', $this->getConf('file_path'));
            $found = false;
            foreach ($paths as $path) {
                if (file_exists($pathfile = trim($path) . DIRECTORY_SEPARATOR . $file)) {
                    require_once $pathfile;
                    break;
                }
            }
            if (!function_exists($func)) {
                return $matches[0];
            }
        }

        // are there additional attribute arguments?
        preg_match_all($this->regexArgs, $matches[2], $args, PREG_PATTERN_ORDER);
        $attr = array();
        foreach ($args[1] as $i=>$name) {
            if ($args[2][$i]{0} == '"' || $args[2][$i]{0} == "'") {
                $attr[$name] = substr($args[2][$i], 1, -1);
            } else {
                $attr[$name] = trim($args[2][$i]);
            }
        }

        // executes the plugin with data and pameters then recursive re-parse for nested or produced plugins
        $res = $func($matches[6], $attr);
        return preg_replace_callback(
                $this->regex,
                array(&$this, 'process'),
                $res
            );
    }
}
?>
