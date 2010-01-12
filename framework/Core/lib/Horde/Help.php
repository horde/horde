<?php
/**
 * The Horde_Help:: class provides an interface to the online help subsystem.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jon Parise <jon@horde.org>
 * @package Core
 */
class Horde_Help
{
    /* Raw help in the string. */
    const SOURCE_RAW = 0;

    /* Help text is in a file. */
    const SOURCE_FILE = 1;

    /**
     * Handle for the XML parser object.
     *
     * @var resource
     */
    protected $_parser;

    /**
     * String buffer to hold the XML help source.
     *
     * @var string
     */
    protected $_buffer = '';

    /**
     * String containing the ID of the requested help entry.
     *
     * @var string
     */
    protected $_reqEntry = '';

    /**
     * String containing the ID of the current help entry.
     *
     * @var string
     */
    protected $_curEntry = '';

    /**
     * String containing the formatted output.
     *
     * @var string
     */
    protected $_output = '';

    /**
     * Boolean indicating whether we're inside a <help> block.
     *
     * @var boolean
     */
    protected $_inHelp = false;

    /**
     * Boolean indicating whether we're inside the requested block.
     *
     * @var boolean
     */
    protected $_inBlock = false;

    /**
     * Boolean indicating whether we're inside a <title> block.
     *
     * @var boolean
     */
    protected $_inTitle = false;

    /**
     * Boolean indicating whether we're inside a heading block.
     *
     * @var boolean
     */
    protected $_inHeading = false;

    /**
     * Hash containing an index of all of the help entries.
     *
     * @var array
     */
    protected $_entries = array();

    /**
     * String containing the charset of the XML data source.
     *
     * @var string
     */
    protected $_charset = 'iso-8859-1';

    /**
     * Hash of user-defined function handlers for the XML elements.
     *
     * @var array
     */
    protected $_handlers = array(
        'help'     =>  '_helpHandler',
        'entry'    =>  '_entryHandler',
        'title'    =>  '_titleHandler',
        'heading'  =>  '_headingHandler',
        'para'     =>  '_paraHandler',
        'ref'      =>  '_refHandler',
        'eref'     =>  '_erefHandler',
        'href'     =>  '_hrefHandler',
        'b'        =>  '_bHandler',
        'i'        =>  '_iHandler',
        'pre'      =>  '_preHandler',
        'tip'      =>  '_tipHandler',
        'warn'     =>  '_warnHandler'
    );

    /**
     * Hash containing an index of all of the search results.
     *
     * @var array
     */
    protected $_search = array();

    /**
     * String containing the keyword for the search.
     *
     * @var string
     */
    protected $_keyword = '';

    /**
     * Constructor.
     *
     * @param integer $source  The source of the XML help data, based on the
     *                         SOURCE_* constants.
     * @param string $arg      Source-dependent argument for this Help
     *                         instance.
     */
    public function __construct($source, $arg = null)
    {
        if (isset($GLOBALS['nls']['charsets'][$GLOBALS['language']])) {
            $this->_charset = $GLOBALS['nls']['charsets'][$GLOBALS['language']];
        }

        /* Populate $this->_buffer based on $source. */
        switch ($source) {
        case self::SOURCE_RAW:
            $this->_buffer = $arg;
            break;

        case self::SOURCE_FILE:
            if (file_exists($arg[0]) && filesize($arg[0])) {
                $this->_buffer = file_get_contents($arg[0]);
            } elseif (file_exists($arg[1]) && filesize($arg[1])) {
                $this->_buffer = file_get_contents($arg[1]);
            } else {
                $this->_buffer = '';
            }
            break;

        default:
            $this->_buffer = '';
            break;
        }
    }

    /**
     * Generates the HTML link that will pop up a help window for the
     * requested topic.
     *
     * @param string $module  The name of the current Horde module.
     * @param string $topic   The help topic to be displayed.
     *
     * @return string  The HTML to create the help link.
     */
    static public function link($module, $topic)
    {
        if (!Horde::showService('help')) {
            return '&nbsp;';
        }

        $url = Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/help/', true);
        $url = Horde_Util::addParameter($url, array('module' => $module,
                                                    'topic' => $topic), null, false);

        return Horde::link($url, _("Help"), 'helplink', 'hordehelpwin', Horde::popupJs($url, array('urlencode' => true)) . 'return false;') .
            Horde::img('help.png', _("Help"), 'width="16" height="16"', $GLOBALS['registry']->getImageDir('horde')) . '</a>';
    }

    /**
     * Looks up the requested entry in the XML help buffer.
     *
     * @param string $entry  String containing the entry ID.
     */
    public function lookup($entry)
    {
        $this->_output = '';
        $this->_reqEntry = Horde_String::upper($entry);
        $this->_init();
        xml_parse($this->_parser, $this->_buffer, true);
    }

    /**
     * Returns a hash of all of the topics in this help buffer
     * containing the keyword specified.
     *
     * @return array  Hash of all of the search results.
     */
    public function search($keyword)
    {
        $this->_init();
        $this->_keyword = $keyword;
        xml_parse($this->_parser, $this->_buffer, true);

        return $this->_search;
    }

    /**
     * Returns a hash of all of the topics in this help buffer.
     *
     * @return array  Hash of all of the topics in this buffer.
     */
    public function topics()
    {
        $this->_init();
        xml_parse($this->_parser, $this->_buffer, true);

        return $this->_entries;
    }

    /**
     * Display the contents of the formatted output buffer.
     */
    public function display()
    {
        echo $this->_output;
    }

    /**
     * Initializes the XML parser.
     *
     * @return boolean  Returns true on success, false on failure.
     * @throws Horde_Exception
     */
    protected function _init()
    {
        if (!isset($this->_parser)) {
            if (!Horde_Util::extensionExists('xml')) {
                throw new Horde_Exception('The XML functions are not available. Rebuild PHP with --with-xml.');
            }

            /* Create a new parser and set its default properties. */
            $this->_parser = xml_parser_create();
            xml_set_object($this->_parser, $this);
            xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
            xml_set_element_handler($this->_parser, '_startElement', '_endElement');
            xml_set_character_data_handler($this->_parser, '_defaultHandler');
        }

        return ($this->_parser != 0);
    }

    /**
     * User-defined function callback for start elements.
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $name    The name of this XML element.
     * @param array $attrs    List of this element's attributes.
     */
    protected function _startElement($parser, $name, $attrs)
    {
        /* Call the assigned handler for this element, if one is
         * available. */
        if (in_array($name, array_keys($this->_handlers))) {
            call_user_func(array(&$this, $this->_handlers[$name]), true, $attrs);
        }
    }

    /**
     * User-defined function callback for end elements.
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $name    The name of this XML element.
     */
    protected function _endElement($parser, $name)
    {
        /* Call the assigned handler for this element, if one is available. */
        if (in_array($name, array_keys($this->_handlers))) {
            call_user_func(array(&$this, $this->_handlers[$name]), false);
        }
    }

    /**
     * User-defined function callback for character data.
     *
     * @param object $parser  Handle to the parser instance.
     * @param string $data    String of character data.
     */
    protected function _defaultHandler($parser, $data)
    {
        $data = Horde_String::convertCharset($data, version_compare(zend_version(), '2', '<') ? $this->_charset : 'UTF-8');
        if ($this->_inTitle) {
            $this->_entries[$this->_curEntry] .= $data;
        }

        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= htmlspecialchars($data);
        }

        if ($this->_keyword) {
            if (stristr($data, $this->_keyword) !== false) {
                $this->_search[$this->_curEntry] = $this->_entries[$this->_curEntry];
            }
        }
    }

    /**
     * XML element handler for the <help> tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes (Not used).
     */
    protected function _helpHandler($startTag, $attrs = array())
    {
        $this->_inHelp = $startTag ?  true : false;
    }

    /**
     * XML element handler for the <entry> tag.
     * Attributes: id
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _entryHandler($startTag, $attrs = array())
    {
        if (!$startTag) {
            $this->_inBlock = false;
        } else {
            $id = Horde_String::upper($attrs['id']);
            $this->_curEntry = $id;
            $this->_entries[$id] = '';
            $this->_inBlock = ($id == $this->_reqEntry);
        }
    }

    /**
     * XML element handler for the <title> tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes (Not used).
     */
    protected function _titleHandler($startTag, $attrs = array())
    {
        $this->_inTitle = $startTag;
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<h1>' : '</h1>';
        }
    }

    /**
     * XML element handler for the <heading> tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param  array $attrs      Additional element attributes (Not used).
     */
    protected function _headingHandler($startTag, $attrs = array())
    {
        $this->_inHeading = $startTag;
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<h2>' : '</h2>';
        }
    }

    /**
     * XML element handler for the <para> tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes (Not used).
     */
    protected function _paraHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<p>' : '</p>';
        }
    }

    /**
     * XML element handler for the <ref> tag.
     * Required attributes: ENTRY, MODULE
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _refHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            if ($startTag && isset($attrs['module']) && isset($attrs['entry'])) {
                $url = Horde_Util::addParameter(Horde::selfUrl(),
                                          array('show' => 'entry',
                                                'module' => $attrs['module'],
                                                'topic'  => $attrs['entry']));
                $this->_output .= Horde::link($url);
            } else {
                $this->_output .= '</a>';
            }
        }
    }

    /**
     * XML element handler for the <eref> tag.
     * Required elements: URL
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _erefHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            if ($startTag) {
                $this->_output .= Horde::link($attrs['url'], null, '', '_blank');
            } else {
                $this->_output .= '</a>';
            }
        }
    }

    /**
     * XML element handler for the <href> tag.
     * Required elements: url, app.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _hrefHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            if ($startTag) {
                $url = Horde::url($GLOBALS['registry']->get('webroot', $attrs['app']) . '/' . $attrs['url']);
                $this->_output .= Horde::link($url, null, '', '_blank');
            } else {
                $this->_output .= '</a>';
            }
        }
    }

    /**
     * XML element handler for the &lt;b&gt; tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes (Not used).
     */
    protected function _bHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<strong>' : '</strong>';
        }
    }

    /**
     * XML element handler for the &lt;i&gt; tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _iHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<em>' : '</em>';
        }
    }

    /**
     * XML element handler for the &lt;pre&gt; tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _preHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<pre>' : '</pre>';
        }
    }

    /**
     * XML element handler for the <tip> tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _tipHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<em class="helpTip">' : '</em>';
        }
    }

    /**
     * XML element handler for the <warn> tag.
     *
     * @param boolean $startTag  Boolean indicating whether this instance is a
     *                           start tag.
     * @param array $attrs       Additional element attributes.
     */
    protected function _warnHandler($startTag, $attrs = array())
    {
        if ($this->_inHelp && $this->_inBlock) {
            $this->_output .= $startTag ? '<em class="helpWarn">' : '</em>';
        }
    }

}
