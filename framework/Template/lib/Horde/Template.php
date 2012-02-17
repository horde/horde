<?php
/**
 * Horde Template system. Adapted from bTemplate by Brian Lozier
 * <brian@massassi.net>.
 *
 * Horde_Template provides a basic template engine with tags, loops,
 * and if conditions. However, it is also a simple interface with
 * several essential functions: set(), fetch(), and
 * parse(). Subclasses or decorators can implement (or delegate) these
 * three methods, plus the options api, and easily implement other
 * template engines (PHP code, XSLT, etc.) without requiring usage
 * changes.
 *
 * Compilation code adapted from code written by Bruno Pedro <bpedro@ptm.pt>.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Template
 */
class Horde_Template
{
    /** The identifier to use for memory-only templates. */
    const TEMPLATE_STRING = '**string';

    /**
     * The Horde_Cache object to use.
     *
     * @var Horde_Cache
     */
    protected $_cache;

    /**
     * Logger.
     *
     * @var Horde_Log_Logger
     */
    protected $_logger;

    /**
     * Option values.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Directory that templates should be read from.
     *
     * @var string
     */
    protected $_basepath = '';

    /**
     * Tag (scalar) values.
     *
     * @var array
     */
    protected $_scalars = array();

    /**
     * Loop tag values.
     *
     * @var array
     */
    protected $_arrays = array();

    /**
     * Path to template source.
     *
     * @var string
     */
    protected $_templateFile = null;

    /**
     * Template source.
     *
     * @var string
     */
    protected $_template = null;

    /**
     * Foreach variable mappings.
     *
     * @var array
     */
    protected $_foreachMap = array();

    /**
     * Foreach variable incrementor.
     *
     * @var integer
     */
    protected $_foreachVar = 0;

    /**
     * preg_match() cache.
     *
     * @var array
     */
    protected $_pregcache = array();

    /**
     * Constructor.
     *
     * @param array $params  The following configuration options:
     * <pre>
     * 'basepath' - (string) The directory where templates are read from.
     * 'cacheob' - (Horde_Cache) A caching object used to cache the output.
     * 'logger' - (Horde_Log_Logger) A logger object.
     * </pre>
     */
    public function __construct($params = array())
    {
        if (isset($params['basepath'])) {
            $this->_basepath = $params['basepath'];
        }

        if (isset($params['cacheob'])) {
            $this->_cache = $params['cacheob'];
        }

        if (isset($params['logger'])) {
            $this->_logger = $params['logger'];
        }
    }

    /**
     * Sets an option.
     * Currently available options are:
     * <pre>
     * 'debug' - Output debugging information to screen
     * 'forcecompile' - Force a compilation on every page load
     * 'gettext' - Activate gettext detection
     * <pre>
     *
     * @param string $option  The option name.
     * @param mixed $val      The option's value.
     */
    public function setOption($option, $val)
    {
        $this->_options[$option] = $val;
    }

    /**
     * Set the template contents to a string.
     *
     * @param string $template  The template text.
     */
    public function setTemplate($template)
    {
        $this->_template = $template;
        $this->_parse();
        $this->_templateFile = self::TEMPLATE_STRING;
    }

    /**
     * Returns an option's value.
     *
     * @param string $option  The option name.
     *
     * @return mixed  The option's value.
     */
    public function getOption($option)
    {
        return isset($this->_options[$option])
            ? $this->_options[$option]
            : null;
    }

    /**
     * Sets a tag, loop, or if variable.
     *
     * @param string|array $tag   Either the tag name or a hash with tag names
     *                            as keys and tag values as values.
     * @param mixed        $var   The value to replace the tag with.
     */
    public function set($tag, $var)
    {
        if (is_array($tag)) {
            foreach ($tag as $tTag => $tVar) {
                $this->set($tTag, $tVar);
            }
        } elseif (is_array($var)) {
            $this->_arrays[$tag] = $var;
        } else {
            $this->_scalars[$tag] = (string) $var;
        }
    }

    /**
     * Returns the value of a tag or loop.
     *
     * @param string $tag  The tag name.
     *
     * @return mixed  The tag value or null if the tag hasn't been set yet.
     */
    public function get($tag)
    {
        if (isset($this->_arrays[$tag])) {
            return $this->_arrays[$tag];
        }
        if (isset($this->_scalars[$tag])) {
            return $this->_scalars[$tag];
        }
        return null;
    }

    /**
     * Fetches a template from the specified file and return the parsed
     * contents.
     *
     * @param string $filename  The file to fetch the template from.
     *
     * @return string  The parsed template.
     */
    public function fetch($filename = null)
    {
        $file = $this->_basepath . $filename;
        $force = $this->getOption('forcecompile');

        if (!is_null($filename) && ($file != $this->_templateFile)) {
            $this->_template = $this->_templateFile = null;
        }

        /* First, check for a cached compiled version. */
        $parts = array(
            'horde_template',
            filemtime($file),
            $file
        );
        if ($this->getOption('gettext')) {
            $parts[] = setlocale(LC_ALL, 0);
        }
        $cacheid = implode('|', $parts);

        if (!$force && is_null($this->_template) && $this->_cache) {
            $this->_template = $this->_cache->get($cacheid, 0);
            if ($this->_template === false) {
                $this->_template = null;
            }
        }

        /* Parse and compile the template. */
        if ($force || is_null($this->_template)) {
            $this->_template = str_replace("\n", " \n", file_get_contents($file));
            $this->_parse();
            if ($this->_cache) {
                $this->_cache->set($cacheid, $this->_template);
                if ($this->_logger) {
                    $this->_logger->log(sprintf('Saved compiled template file for "%s".', $file), 'DEBUG');
                }
            }
        }

        $this->_templateFile = $file;

        /* Template debugging. */
        if ($this->getOption('debug')) {
            echo '<pre>' . htmlspecialchars($this->_template) . '</pre>';
        }

        return $this->parse();
    }

    /**
     * Parses all variables/tags in the template.
     *
     * @param string $contents  The unparsed template.
     *
     * @return string  The parsed template.
     */
    public function parse($contents = null)
    {
        if (!is_null($contents)) {
            $this->setTemplate(str_replace("\n", " \n", $contents));
        }

        /* Evaluate the compiled template and return the output. */
        ob_start();
        eval('?>' . $this->_template);
        return is_null($contents)
            ? ob_get_clean()
            : str_replace(" \n", "\n", ob_get_clean());
    }

    /**
     * Parses all variables/tags in the template.
     */
    protected function _parse()
    {
        // Escape XML instructions.
        $this->_template = preg_replace('/\?>|<\?/', '<?php echo \'$0\' ?>', $this->_template);

        // Parse gettext tags, if the option is enabled.
        if ($this->getOption('gettext')) {
            $this->_parseGettext();
        }

        // Process ifs.
        $this->_parseIf();

        // Process loops and arrays.
        $this->_parseLoop();

        // Process base scalar tags.  Needs to be after _parseLoop() as we
        // rely on _foreachMap().
        $this->_parseTags();

        // Finally, process any associative array scalar tags.
        $this->_parseAssociativeTags();
    }

    /**
     * Parses gettext tags.
     *
     * @todo Convert to use Horde_Translation.
     */
    protected function _parseGettext()
    {
        if (preg_match_all("/<gettext>(.+?)<\/gettext>/s", $this->_template, $matches, PREG_SET_ORDER)) {
            $replace = array();
            foreach ($matches as $val) {
                // eval gettext independently so we can embed tempate tags
                $code = 'echo _(\'' . str_replace("'", "\\'", $val[1]) . '\');';
                ob_start();
                eval($code);
                $replace[$val[0]] = ob_get_clean();
            }

            $this->_doReplace($replace);
        }
    }

    /**
     * Parses 'if' statements.
     *
     * @param string $key  The key prefix to parse.
     */
    protected function _parseIf($key = null)
    {
        $replace = array();

        foreach ($this->_doSearch('if', $key) as $val) {
            $replace[$val[0]] = '<?php if (!empty(' . $this->_generatePHPVar('scalars', $val[1]) . ') || !empty(' . $this->_generatePHPVar('arrays', $val[1]) . ')): ?>';
            $replace[$val[2]] = '<?php endif; ?>';

            // Check for else statement.
            foreach ($this->_doSearch('else', $key) as $val2) {
                $replace[$val2[0]] = '<?php else: ?>';
                $replace[$val2[2]] = '';
            }
        }

        $this->_doReplace($replace);
    }

    /**
     * Parses the given array for any loops or other uses of the array.
     *
     * @param string $key  The key prefix to parse.
     */
    protected function _parseLoop($key = null)
    {
        $replace = array();

        foreach ($this->_doSearch('loop', $key) as $val) {
            $divider = null;

            // See if we have a divider.
            if (preg_match("/<divider:" . $val[1] . ">(.*)<\/divider:" . $val[1] . ">/sU", $this->_template, $m)) {
                $divider = $m[1];
                $replace[$m[0]] = '';
            }

            if (!isset($this->_foreachMap[$val[1]])) {
                $this->_foreachMap[$val[1]] = ++$this->_foreachVar;
            }
            $varId = $this->_foreachMap[$val[1]];
            $var = $this->_generatePHPVar('arrays', $val[1]);

            $replace[$val[0]] = '<?php ' .
                (($divider) ? '$i' . $varId . ' = count(' . $var . '); ' : '') .
                'foreach (' . $this->_generatePHPVar('arrays', $val[1]) . ' as $k' . $varId . ' => $v' . $varId . '): ?>';
            $replace[$val[2]] = '<?php ' .
                (($divider) ? 'if (--$i' . $varId . ' != 0) { echo \'' . $divider . '\'; }; ' : '') .
                'endforeach; ?>';

            // Parse ifs.
            $this->_parseIf($val[1]);

            // Parse interior loops.
            $this->_parseLoop($val[1]);

            // Replace scalars.
            $this->_parseTags($val[1]);
        }

        $this->_doReplace($replace);
    }

    /**
     * Replaces 'tag' tags with their PHP equivalents.
     *
     * @param string $key  The key prefix to parse.
     */
    protected function _parseTags($key = null)
    {
        $replace = array();

        foreach ($this->_doSearch('tag', $key, true) as $val) {
            $replace_text = '<?php ';
            if (isset($this->_foreachMap[$val[1]])) {
                $var = $this->_foreachMap[$val[1]];
                $replace_text .= 'if (isset($v' . $var . ')) { echo is_array($v' . $var . ') ? $k' . $var . ' : $v' . $var . '; } else';
            }
            $var = $this->_generatePHPVar('scalars', $val[1]);
            $replace[$val[0]] = $replace_text . 'if (isset(' . $var . ')) { echo ' . $var . '; } ?>';
        }

        $this->_doReplace($replace);
    }

    /**
     * Parse associative tags (i.e. <tag:foo.bar />).
     */
    protected function _parseAssociativeTags()
    {
        $replace = array();

        foreach ($this->_pregcache['tag'] as $key => $val) {
            $parts = explode('.', $val[1]);
            $var = '$this->_arrays[\'' . $parts[0] . '\'][\'' . $parts[1] . '\']';
            $replace[$val[0]] = '<?php if (isset(' . $var . ')) { echo ' . $var . '; } ?>';
            unset($this->_pregcache['tag'][$key]);
        }

        $this->_doReplace($replace);
    }

    /**
     * Output the correct PHP variable string for use in template space.
     */
    protected function _generatePHPVar($tag, $key)
    {
        $out = '';

        $a = explode('.', $key);
        $a_count = count($a);

        if ($a_count == 1) {
            switch ($tag) {
            case 'arrays':
                $out = '$this->_arrays';
                break;

            case 'scalars':
                $out = '$this->_scalars';
                break;
            }
        } else {
            $out = '$v' . $this->_foreachMap[implode('.', array_slice($a, 0, -1))];
        }

        return $out . '[\'' . end($a) . '\']';
    }

    /**
     * TODO
     */
    protected function _doSearch($tag, $key, $noclose = false)
    {
        $out = array();
        $level = (is_null($key)) ? 0 : substr_count($key, '.') + 1;

        if (!isset($this->_pregcache[$key])) {
            $regex = ($noclose) ?
                "/<" . $tag . ":(.+?)\s\/>/" :
                "/<" . $tag . ":([^>]+)>/";
            preg_match_all($regex, $this->_template, $this->_pregcache[$tag], PREG_SET_ORDER);
        }

        foreach ($this->_pregcache[$tag] as $pkey => $val) {
            $val_level = substr_count($val[1], '.');
            $add = false;
            if (is_null($key)) {
                $add = !$val_level;
            } else {
                $add = (($val_level == $level) &&
                        (strpos($val[1], $key . '.') === 0));
            }
            if ($add) {
                if (!$noclose) {
                    $val[2] = '</' . $tag . ':' . $val[1] . '>';
                }
                $out[] = $val;
                unset($this->_pregcache[$tag][$pkey]);
            }
        }

        return $out;
    }

    /**
     * TODO
     */
    protected function _doReplace($replace)
    {
        if (empty($replace)) {
            return;
        }

        $search = array();

        foreach (array_keys($replace) as $val) {
            $search[] = '/' . preg_quote($val, '/') . '/';
        }

        $this->_template = preg_replace($search, array_values($replace), $this->_template);
    }

}
