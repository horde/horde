<?php
/**
 * ActiveSync specific WBXML handling. This (and all related code) needs to be
 * refactored to use XML_WBXML, or the H4 equivelant when it is written...
 *
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
class Horde_ActiveSync_Wbxml_Encoder
{
    private $_dtd;
    private $_out;
    private $_tagcp;
    private $_attrcp;
    private $_logStack = array();

    // We use a delayed output mechanism in which we only output a tag when it actually has something
    // in it. This can cause entire XML trees to disappear if they don't have output data in them; Ie
    // calling 'startTag' 10 times, and then 'endTag' will cause 0 bytes of output apart from the header.
    // Only when content() is called do we output the current stack of tags=
    private $_stack = array();

    /**
     * Const'r
     *
     * @param stream $output
     * @param array $dtd
     * @param array $config
     *
     * @return Horde_ActiveSync_Wbxml_Encoder
     */
    function __construct($output, $dtd)
    {
        $this->_out = $output;
        $this->_tagcp = 0;
        $this->_attrcp = 0;

        // reverse-map the DTD
        foreach ($dtd['namespaces'] as $nsid => $nsname) {
            $this->_dtd['namespaces'][$nsname] = $nsid;
        }

        foreach ($dtd['codes'] as $cp => $value) {
            $this->_dtd['codes'][$cp] = array();
            foreach ($dtd['codes'][$cp] as $tagid => $tagname) {
                $this->_dtd['codes'][$cp][$tagname] = $tagid;
            }
        }
    }

    public function setLogger(Horde_Log_Logger $logger)
    {
        $this->_logger = $logger;
    }
    /**
     *
     * @return unknown_type
     */
    public function startWBXML()
    {
        header('Content-Type: application/vnd.ms-sync.wbxml');
        $this->_outByte(0x03); // WBXML 1.3
        $this->_outMBUInt(0x01); // Public ID 1
        $this->_outMBUInt(106); // UTF-8
        $this->_outMBUInt(0x00); // string table length (0)
    }

    /**
     *
     * @param $tag
     * @param $attributes
     * @param $nocontent
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

            // If 'nocontent' is specified, then apparently the user wants to force
            // output of an empty tag, and we therefore output the stack here
        } else {
            $this->_outputStack();
            $this->_startTag($tag, $attributes, $nocontent);
        }
    }

    /**
     *
     * @return void
     */
    public function endTag()
    {
        $stackelem = array_pop($this->_stack);
        // Only output end tags for items that have had a start tag sent
        if ($stackelem['sent']) {
            $this->_endTag();
        }
    }

    /**
     *
     * @param $content
     *
     * @return void
     */
    public function content($content)
    {
        // We need to filter out any \0 chars because it's the string terminator in WBXML. We currently
        // cannot send \0 characters within the XML content anywhere.
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
     * @TODO: Not 100% sure this can be private
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

    // Outputs an actual start tag
    private function _startTag($tag, $attributes = false, $nocontent = false)
    {
        $this->_logStartTag($tag, $attributes, $nocontent);
        $mapping = $this->_getMapping($tag);
        if (!$mapping) {
            return false;
        }

        if ($this->_tagcp != $mapping['cp']) {
            $this->_outSwitchPage($mapping['cp']);
            $this->_tagcp = $mapping['cp'];
        }

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
     * @param $content
     *
     * @return unknown_type
     */
    private function _content($content)
    {
        $this->_logContent($content);
        $this->_outByte(Horde_ActiveSync_Wbxml::STR_I);
        $this->_outTermStr($content);
    }

    // Outputs an actual end tag
    function _endTag() {
        $this->_logEndTag();
        $this->_outByte(Horde_ActiveSync_Wbxml::END);
    }

    /**
     *
     * @param $byte
     * @return unknown_type
     */
    private function _outByte($byte)
    {
        fwrite($this->_out, chr($byte));
    }

    /**
     *
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
     *
     * @param $content
     * @return unknown_type
     */
    private function _outTermStr($content)
    {
        fwrite($this->_out, $content);
        fwrite($this->_out, chr(0));
    }

    /**
     *
     * @return unknown_type
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
     *
     * @param $tag
     * @return unknown_type
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
     *
     * @param $fulltag
     * @return unknown_type
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
     *
     * @param $tag
     * @param $attr
     * @param $nocontent
     * @return unknown_type
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
     *
     * @return unknown_type
     */
    private function _logEndTag()
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        $tag = array_pop($this->_logStack);
        $this->_logger->debug(sprintf('O %s <%s/>', $spaces, $tag));
    }

    /**
     *
     * @param unknown_type $content
     * @return unknown_type
     */
    private function _logContent($content)
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        $this->_logger->debug('O ' . $spaces . $content);
    }

}