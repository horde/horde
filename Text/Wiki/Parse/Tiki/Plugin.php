<?php
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
    var $regexArgs =  '#(\w+?)=>?("[^"]+"|\'[^\']+\'|[^"\'][^,]+)#';

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

    //var $regex = '/^({([A-Z]+?)\((.+)?\)})((.+)({\2}))?(\s|$)/Umsi';
    var $regex = '/\{([A-Z]+?)\((.*?)\)}((?:(?R)|.)*?)\{\1}/msi';


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
        $res = $func($matches[3], $attr);
        return preg_replace_callback(
                $this->regex,
                array(&$this, 'process'),
                $res
            );
    }
}
?>
