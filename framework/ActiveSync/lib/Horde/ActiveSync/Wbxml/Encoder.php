<?php
/**
 * ActiveSync specific WBXML encoding. No need to build xml document in memory
 * since we stream the actual binary data as we build it. Contains code from
 * the Z-Push project. Original file header below.
 *
 * @copyright 2010-2011 The Horde Project (http://www.horde.org)
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
/**
 * File      :   wbxml.php
 * Project   :   Z-Push
 * Descr     :   WBXML mapping file
 *
 * Created   :   01.10.2007
 *
 * � Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_Wbxml_Encoder extends Horde_ActiveSync_Wbxml
{
    /**
     * Output stream - normally the php output stream, but can technically take
     * any writable stream (for testing).
     *
     * @var stream
     */
    private $_out;

    /**
     * Track the codepage for the currently output tag so we know when to
     * switch codepages.
     *
     * @var integer
     */
    private $_tagcp;

    /**
     * Used to hold log entries for each tag so we can only output the log
     * entries for the tags that are actually sent (@see $_stack).
     *
     * @var array
     */
    private $_logStack = array();

    /**
     * Cache the tags to output. The stack is output when content() is called.
     * We only output tags when they actually contain something. i.e. calling
     * startTag() 10 times, then endTag() will cause 0 bytes of output apart
     * from the header.
     *
     * @var array
     */
    private $_stack = array();

    /**
     * Const'r
     *
     * @param stream $output
     * @param array $config
     *
     * @return Horde_ActiveSync_Wbxml_Encoder
     */
    function __construct($output)
    {
        $this->_out = $output;
        $this->_tagcp = 0;

        /* reverse-map the DTD */
        $dtd = array();
        foreach ($this->_dtd['namespaces'] as $nsid => $nsname) {
            $dtd['namespaces'][$nsname] = $nsid;
        }

        foreach ($this->_dtd['codes'] as $cp => $value) {
            $dtd['codes'][$cp] = array();
            foreach ($this->_dtd['codes'][$cp] as $tagid => $tagname) {
                $dtd['codes'][$cp][$tagname] = $tagid;
            }
        }

        $this->_dtd = $dtd;
    }

    /**
     * Setter for the logger
     *
     * @param Horde_Log_Logger $logger  The logger instance
     */
    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }

    /**
     * Starts the wbxml output
     *
     * @return void
     */
    public function startWBXML()
    {
        header('Content-Type: application/vnd.ms-sync.wbxml');
        $this->outputWbxmlHeader();
    }

    public function outputWbxmlHeader()
    {
        $this->_outByte(0x03);   // WBXML 1.3
        $this->_outMBUInt(0x01); // Public ID 1
        $this->_outMBUInt(106);  // UTF-8
        $this->_outMBUInt(0x00); // string table length (0)
    }

    /**
     * Start output for the specified tag
     *
     * @param string $tag        The textual representation of the tag to start
     * @param mixed $attributes  Any attributes for the start tag
     * @param boolean $nocontent Force output of empty tags
     *
     * @return void
     */
    public function startTag($tag, $attributes = false, $nocontent = false)
    {
        $stackelem = array();
        if (!$nocontent) {
            $stackelem['tag'] = $tag;
            $stackelem['attributes'] = $attributes;
            $stackelem['nocontent'] = $nocontent;
            $stackelem['sent'] = false;
            array_push($this->_stack, $stackelem);
        } else {
            /* Flush the stack if we want to force empty tags */
            $this->_outputStack();
            $this->_startTag($tag, $attributes, $nocontent);
        }
    }

    /**
     * Output the end tag
     *
     * @return void
     */
    public function endTag()
    {
        $stackelem = array_pop($this->_stack);
        /* Only output end tags for items that have had a start tag sent */
        if ($stackelem['sent']) {
            $this->_endTag();
        }
    }

    /**
     * Output the tag content
     *
     * @param string $content  The value to output for this tag
     *
     * @return void
     */
    public function content($content)
    {
        /* Filter out \0 since it's a string terminator in wbxml. We cannot send
         * \0 within the xml content */
        $content = str_replace('\0', '', $content);
        if ('x' . $content == 'x') {
            return;
        }
        $this->_outputStack();
        $this->_content($content);
    }

    /**
     * Output any tags on the stack that haven't been output yet
     *
     * @return void
     */
    private function _outputStack()
    {
        for ($i=0; $i < count($this->_stack); $i++) {
            if (!$this->_stack[$i]['sent']) {
                $this->_startTag($this->_stack[$i]['tag'], $this->_stack[$i]['attributes'], $this->_stack[$i]['nocontent']);
                $this->_stack[$i]['sent'] = true;
            }
        }
    }

    /**
     * Actually outputs the start tag
     *
     * @param string $tag @see Horde_ActiveSync_Wbxml_Encoder::startTag
     * @param mixed $attributes @see Horde_ActiveSync_Wbxml_Encoder::startTag
     * @param boolean $nocontent @see Horde_ActiveSync_Wbxml_Encoder::startTag
     *
     * @return void
     */
    private function _startTag($tag, $attributes = false, $nocontent = false)
    {
        $this->_logStartTag($tag, $attributes, $nocontent);
        $mapping = $this->_getMapping($tag);
        if (!$mapping) {
           return false;
        }

        /* Make sure we don't need to switch code pages */
        if ($this->_tagcp != $mapping['cp']) {
            $this->_outSwitchPage($mapping['cp']);
            $this->_tagcp = $mapping['cp'];
        }

        /* Build and send the code */
        $code = $mapping['code'];
        if (isset($attributes) && is_array($attributes) && count($attributes) > 0) {
            $code |= 0x80;
        }
        if (!isset($nocontent) || !$nocontent) {
            $code |= 0x40;
        }
        $this->_outByte($code);
        if ($code & 0x80) {
            $this->_outAttributes($attributes);
        }
    }

    /**
     * Outputs data
     *
     * @param string $content  The content to send
     *
     * @return void
     */
    private function _content($content)
    {
        $this->_logContent($content);
        $this->_outByte(Horde_ActiveSync_Wbxml::STR_I);
        $this->_outTermStr($content);
    }

    /**
     * Output the endtag
     *
     * @return void
     */
    function _endTag() {
        $this->_logEndTag();
        $this->_outByte(Horde_ActiveSync_Wbxml::END);
    }

    /**
     * Output a single byte to the stream
     *
     * @param byte $byte
     * @return unknown_type
     */
    private function _outByte($byte)
    {
        fwrite($this->_out, chr($byte));
    }

    /**
     * Outputs an MBUInt to the stream
     * @param $uint
     * @return unknown_type
     */
    private function _outMBUInt($uint)
    {
        while (1) {
            $byte = $uint & 0x7f;
            $uint = $uint >> 7;
            if ($uint == 0) {
                $this->_outByte($byte);
                break;
            } else {
                $this->_outByte($byte | 0x80);
            }
        }
    }

    /**
     * Output a string along with the terminator.
     *
     * @param string $content  The string
     *
     * @return void
     */
    private function _outTermStr($content)
    {
        fwrite($this->_out, $content);
        fwrite($this->_out, chr(0));
    }

    /**
     * Output attributes
     *
     * @return void
     */
    private function _outAttributes()
    {
        // We don't actually support this, because to do so, we would have
        // to build a string table before sending the data (but we can't
        // because we're streaming), so we'll just send an END, which just
        // terminates the attribute list with 0 attributes.
        $this->_outByte(Horde_ActiveSync_Wbxml::END);
    }

    /**
     *
     * @param $page
     * @return unknown_type
     */
    private function _outSwitchPage($page)
    {
        $this->_outByte(Horde_ActiveSync_Wbxml::SWITCH_PAGE);
        $this->_outByte($page);
    }

    /**
     * Obtain the wbxml mapping for the given tag
     *
     * @param string $tag
     *
     * @return array
     */
    private function _getMapping($tag)
    {
        $mapping = array();
        $split = $this->_splitTag($tag);
        if (isset($split['ns'])) {
            $cp = $this->_dtd['namespaces'][$split['ns']];
        } else {
            $cp = 0;
        }

        $code = $this->_dtd['codes'][$cp][$split['tag']];
        $mapping['cp'] = $cp;
        $mapping['code'] = $code;

        return $mapping;
    }

    /**
     * Split a tag into it's atomic parts
     *
     * @param string $fulltag  The full tag name
     *                         (e.g. POOMCONTACTS:Email1Address)
     *
     * @return array  An array containing the namespace and tagname
     */
    private function _splitTag($fulltag)
    {
        $ns = false;
        $pos = strpos($fulltag, chr(58)); // chr(58) == ':'
        if ($pos) {
            $ns = substr($fulltag, 0, $pos);
            $tag = substr($fulltag, $pos+1);
        } else {
            $tag = $fulltag;
        }

        $ret = array();
        if ($ns) {
            $ret['ns'] = $ns;
        }
        $ret['tag'] = $tag;

        return $ret;
    }

    /**
     * Log the start tag output
     *
     * @param string $tag
     * @param mixed $attr
     * @param boolean $nocontent
     *
     * @return void
     */
    private function _logStartTag($tag, $attr, $nocontent)
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        if ($nocontent) {
            $this->_logger->debug(sprintf('O %s <%s/>', $spaces, $tag));
        } else {
            array_push($this->_logStack, $tag);
            $this->_logger->debug(sprintf('O %s <%s>', $spaces, $tag));
        }
    }

    /**
     * Log the endtag output
     *
     * @return void
     */
    private function _logEndTag()
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        $tag = array_pop($this->_logStack);
        $this->_logger->debug(sprintf('O %s <%s/>', $spaces, $tag));
    }

    /**
     * Log the content output
     *
     * @param string $content  The output
     *
     * @return void
     */
    private function _logContent($content)
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        $this->_logger->debug('O ' . $spaces . $content);
    }

}