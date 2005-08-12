<?php
// vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4:
/**
 * HTML_BBCodeParser: Transforms BBCode in XHTML (Text_Wiki version)
 *
 * PHP versions 4 and 5
 *
 * @category   HTML
 * @package    HTML_BBCodeParser
 * @author     Stijn de Reede  <sjr@gmx.co.uk>
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  1997-2005 The PHP Group
 * @copyright  2005 bertrand Gugger
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/HTML_BBCodeParser
 * @see        Text_Wiki
 */

/**
 * Text transformation engine
 */
require_once 'Text/Wiki/BBCode.php';

/**
 * PEAR base class to get the static properties
 */
if (!defined('HTML_BBCODEPARSER_V2')) {
    require_once 'PEAR.php';
}

/**
 * Base HTML_BBCodeParser class to transform BBCode in XHTML
 *
 * This is a parser to replace UBB style tags with their xhtml equivalents.
 * It's the refundation of the class using the Text_Wiki transform engine
 *
 * Usage:
 *   $parser = new HTML_BBCodeParser();
 *   $parser->setText('normal [b]bold[/b] and normal again');
 *   $parser->parse();
 *   echo $parser->getParsed();
 * or:
 *   $parser = new HTML_BBCodeParser();
 *   echo $parser->qparse('normal [b]bold[/b] and normal again');
 * or:
 *   echo HTML_BBCodeParser::staticQparse('normal [b]bold[/b] and normal again');
 *
 * Setting the options from the ini file:
 *   $config = parse_ini_file('BBCodeParser.ini', true);
 *   $options = &PEAR::getStaticProperty('HTML_BBCodeParser', '_options');
 *   $options = $config['HTML_BBCodeParser'];
 *   unset($options);
 *
 * @category   HTML
 * @package    HTML_BBCodeParser
 * @author     Stijn de Reede  <sjr@gmx.co.uk>
 * @author     Bertrand Gugger <bertrand@toggg.com>
 * @copyright  1997-2005 The PHP Group
 * @copyright  2005 bertrand Gugger
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/HTML_BBCodeParser
 * @see        Text_Wiki::BBCode()
 */
class HTML_BBCodeParser extends Text_Wiki_BBCode
{
    /**
     * A string containing the input
     *
     * @access   private
     * @var      string
     */
    var $_text          = '';

    /**
    * A string containing the parsed version of the text
    *
    * @access   private
    * @var      string
    */
    var $_parsed        = '';

    /**
    * An array of options, filled by an ini file or through the contructor
    *
    * @access   private
    * @var      array
    */
    var $_options = array(  'quotestyle'    => 'double', // fixed, ignored
                            'quotewhat'     => 'all', // fixed, ignored
                            'open'          => '[', // fixed, rejected
                            'close'         => ']', // fixed, rejected
                            'xmlclose'      => true, // fixed, ignored
                            'filters'       => 'Basic',
                            'rules'         => array(),
                            'parse'         => array(),
                            'render'        => array()
                         );

    /**
    * Constructor, initialises the options and filters
    *
    * Sets the private variable _options with base options defined with
    * &PEAR::getStaticProperty(), overwriting them with (if present)
    * the argument to this method.
    * Then it sets the extra options to properly escape the tag
    * characters in preg_replace() etc. The set options are
    * then stored back with &PEAR::getStaticProperty(), so that the filter
    * classes can use them.
    * All the filters in the options are initialised and their defined tags
    * are copied into the private variable _definedTags.
    *
    * @param    mixed array or string           options or ini file to use, can be left out
    * @return   none
    * @access   public
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function HTML_BBCodeParser($options = array())
    {
        // instantiate the Text_Wiki transformer
        parent::Text_Wiki_BBCode();

        // config file (.ini) ?
        if (is_string($options)) {
            $options = HTML_BBCodeParser::parseIniFile($options);
        }

        // set the already set options
        if (!defined('HTML_BBCODEPARSER_V2')) {
            $baseoptions = PEAR::getStaticProperty('HTML_BBCodeParser', '_options');
            if (is_array($baseoptions)) {
                foreach ($baseoptions as  $k => $v)  {
                    $this->_options[$k] = $v;
                }
            }
        }

        // set the options passed as an argument
        foreach ($options as $k => $v )  {
           $this->_options[$k] = $v;
        }

        // open and close tags are fixed by Text_Wiki_BBCode
        if ((isset($this->_options['open'])  && ($this->_options['open']  != '['))
         || (isset($this->_options['close']) && ($this->_options['close'] != ']'))) {
            return
             "<p>Sorry, open/close tags are fixed to '[' and ']', put a RFE if neede</p>\n";
        }
        // set the rules
        $rules = array_merge(
            // mandatory rules
            array('Prefilter', 'Delimiter'),
            //  old style ?
            isset($this->_options['filters']) ?
                HTML_BBCodeParser::filtersToRules($this->_options['filters']) : array(),
            //  new style ?
            isset($this->_options['rules']) ?
                is_array($this->_options['rules']) ?
                    $this->_options['rules'] : explode(',', $this->_options['rules'])
                : array()
        );

        // filter rules so their order is kept
        foreach ($this->rules as $rule) {
            if (!in_array( $rule, $rules)) {
                $this->deleteRule($rule);
            }
        }
        if (isset($options['parse'][$options['set_parse']])) {
            $this->parseConf = $options['parse'][$options['set_parse']];
        }
        if (isset($options['format'])) {
            foreach (array_keys($options['format']) as $render) {
                if (!isset($options['set_render'])
                  || in_array($render, $options['set_render'])) {
                    $this->setFormatConf($render, $options['format'][$render]);
                }
            }
        }
        if (isset($options['render'])) {
            foreach (array_keys($options['render']) as $render) {
                if (in_array($render, $options['set_render'])) {
                    $this->renderConf[$render] = $options['render'][$render];
                }
            }
        }
//        var_dump( $options);var_dump($this->parseConf);var_dump($this->formatConf);var_dump($this->renderConf); die();
    }

    /**
     * Prune (unset) empty arrays in options
     *
     * @param    string $iniFile the configuration file name (default 'BBCodeParser.ini')
     * @return   array the corresponding options as a tree array general|Parse|Render*Format*Rule
     * @access   public
     * @static
     */
    function pruneOptions(&$options)
    {
        foreach (array_keys($options) as $k0) {
            if (is_array($options[$k0])) {
                if ($options[$k0]) {
                    HTML_BBCodeParser::pruneOptions($options[$k0]);
                }
                if (!$options[$k0]) {
                    unset($options[$k0]);
                }
            }
        }
    }

    /**
     * Parse configuration file to set an option array
     *
     * @param    string $iniFile the configuration file name (default 'BBCodeParser.ini')
     * @return   array the corresponding options
     *           as a tree general|parse|render|format*Format*Rule
     * @access   public
     * @static
     */
    function parseIniFile($iniFile = 'BBCodeParser.ini')
    {
        static $isArray = array(
            'set_render', 'filters', 'rules',
            'schemes', 'extensions', 'refused', 'prefixes', 'img_ext');
        $options = array();
        // Parse the ini file
        $config = parse_ini_file($iniFile, true);
        // Normalize if old config
        if (isset($config['HTML_BBCodeParser'])) {
            $config = array_merge($config['HTML_BBCodeParser'], $config);
            unset($config['HTML_BBCodeParser']);
        }
        // proceed each section or general items
        foreach ($config as $name => $section) {
            if (is_string($section)) {
                $options[$name] = in_array($name, $isArray) ?
                            explode(',', $section) : $section;
                continue;
            }
            $keys = explode('_', $name);
            $here = & $options;
            foreach ($keys as $key) {
                if (!isset($here[$key])) {
                    $here[$key] = array();
                }
                $here = & $here[$key];
            }
            $smiley = (count($keys) == 3) && ($keys[0] == 'parse') && ($keys[2] == 'Smiley');
            foreach ($section as $itk => $item) {
                if ($item === '') {
                    continue;
                }
                if ($smiley && (substr($itk, 0, 7) == 'smiley_')) {
                    $words = explode(' ', $item);
                    $equal = false;
                    $variante = array();
                    for ($i = 1; $i < count($words); $i++) {
                        if ($equal) {
                            $variante[] = $words[$i];
                            $equal = false;
                            continue;
                        }
                        if (!$equal = ($words[$i] == '=')) {
                            break;
                        }
                    }
                    $here[$words[0]] = array_merge(
                        array(
                            substr($itk, 7),
                            $equal || (count($words) == $i + 1) ?
                                $itk
                              : implode(' ', array_slice($words, $i))
                        ),
                        $variante);
                } else {
                    $here[$itk] = in_array($itk, $isArray) ?
                                explode(',', $item) : $item;
                }
            }
        }
        HTML_BBCodeParser::pruneOptions($options);
        return $options;
    }

    /**
     * Sets rules list from Filters (groups of rules)
     *
     * @param    mixed filters to set as a comma separated string or array
     * @return   array the corresponding rules
     * @access   public
     * @static
     */
    function filtersToRules($filters = array())
    {
        $conv = array(
            // todo: , 'Strike', ' Subscript', 'Superscript'
            'Basic' => array('Bold', 'Italic', 'Underline'),
            'Extended' => array('Colortext', 'Font', 'Blockquote', 'Code'), //todo: , 'align'
            'Links' => array('Url'),
            'Images' => array('Image'),
            'Lists' => array('List'),
            'Email' => array('Url')
        );
        if (!is_array($filters)) {
            $filters = explode(',', $filters);
        }
        foreach ($conv as $filter => $rules) {
            if (!in_array( $filter, $filters)) {
                unset($conv[$filter]);
            }
        }
        return $conv;
    }

    /**
    * Sets text in the object to be parsed
    *
    * @param    string          the text to set in the object
    * @return   none
    * @access   public
    * @see      getText()
    * @see      $_text
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function setText($str)
    {
        $this->_text = $str;
    }

    /**
    * Gets the unparsed text from the object
    *
    * @return   string          the text set in the object
    * @access   public
    * @see      setText()
    * @see      $_text
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function getText()
    {
        return $this->_text;
    }

    /**
    * Gets the preparsed text from the object
    *
    * @return   string          the text set in the object
    * @access   public
    * @see      _preparse()
    * @see      $_preparsed
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function getPreparsed()
    {
        return $this->_preparsed;
    }

    /**
    * Gets the parsed text from the object
    *
    * @return   string          the parsed text set in the object
    * @access   public
    * @see      parse()
    * @see      $_parsed
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function getParsed()
    {
        return $this->_parsed;
    }

    /**
    * Parses the text set in the object
    *
    * @param    string $text : if set, that's a call to the parent Text_Wiki::parse()
    * @return   none
    * @access   public
    * @see      Text_Wiki::parse()
    * @see      Text_Wiki::render()
    */
    function parse($text = null)
    {
        if (isset($text)) {
            parent::parse($text);
            return;
        }
        parent::parse($this->_text);
        $this->_preparsed = $this->source;
        $this->_parsed = parent::render('Xhtml');
    }

    /**
    * Quick method to do setText(), parse() and getParsed at once
    *
    * @return   none
    * @access   public
    * @see      parse()
    * @see      $_text
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function qparse($str)
    {
        $this->_text = $str;
        $this->parse();
        return $this->_parsed;
    }

    /**
    * Quick static method to do setText(), parse() and getParsed at once
    *
    * @return   none
    * @access   public
    * @see      parse()
    * @see      $_text
    * @author   Stijn de Reede  <sjr@gmx.co.uk>
    */
    function staticQparse($str)
    {
        $p = new HTML_BBCodeParser();
        $str = $p->qparse($str);
        unset($p);
        return $str;
    }
}
?>
