<?php
/**
 * Horde_ActiveSync_Wbxml_Encoder::
 *
 * Portions of this class were ported from the Z-Push project:
 *   File      :   wbxml.php
 *   Project   :   Z-Push
 *   Descr     :   WBXML mapping file
 *
 *   Created   :   01.10.2007
 *
 *   � Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Wbxml_Encoder::  Encapsulates all Wbxml encoding from
 * server to client.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Wbxml_Encoder extends Horde_ActiveSync_Wbxml
{
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
     * Flag to indicate if we are outputing multipart binary data during e.g.,
     * ITEMOPERATION requests.
     *
     * @var boolean
     */
    public $multipart;

    /**
     * Const'r
     *
     * @param stream $output  The output stream
     *
     * @return Horde_ActiveSync_Wbxml_Encoder
     */
    function __construct($output)
    {
        parent::__construct($output);

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
     * Starts the wbxml output.
     *
     * @param boolean $multipart  Indicates we need to output mulitpart binary
     *                            binary data. See MS-ASCMD 2.2.1.8.1
     *
     */
    public function startWBXML($multipart = false)
    {
        $this->multipart = $multipart;
        $this->outputWbxmlHeader();
    }

    /**
     * Output the Wbxml header to the output stream.
     *
     */
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
     * @param string $tag            The name of the tag to start
     * @param mixed $attributes      Any attributes for the start tag
     * @param boolean $output_empty  Force output of empty tags
     *
     */
    public function startTag($tag, $attributes = false, $output_empty = false)
    {
        $stackelem = array();
        if (!$output_empty) {
            $stackelem['tag'] = $tag;
            $stackelem['attributes'] = $attributes;
            $stackelem['nocontent'] = $output_empty;
            $stackelem['sent'] = false;
            array_push($this->_stack, $stackelem);
        } else {
            /* Flush the stack if we want to force empty tags */
            $this->_outputStack();
            $this->_startTag($tag, $attributes, $output_empty);
        }
    }

    /**
     * Output the end tag
     *
     */
    public function endTag()
    {
        $stackelem = array_pop($this->_stack);
        if ($stackelem['sent']) {
            $this->_endTag();
            if (count($this->_stack) == 0 && $this->multipart) {
                $len = ob_get_length();
                $data = ob_get_contents();
                ob_end_clean();
                ob_start();
                $blockstart = ((count($this->_parts) + 1) * 2) * 4 + 4;
                $sizeinfo = pack('iii', count($this->_parts) + 1, $blockstart, $len);
                $this->_logger->debug('Multipart Debug Output Total parts ' . (count($this->_parts) + 1));
                foreach ($this->_parts as $bp) {
                    $blockstart = $blockstart + $len;
                    if (is_resource($bp)) {
                        rewind($bp);
                        fseek($bp, 0, SEEK_END);
                        $len = ftell($bp);
                    } else {
                        $len = strlen(bin2hex($bp)) / 2;
                    }
                    $sizeinfo .= pack('ii', $blockstart, $len);
                }
                fwrite($this->_stream, $sizeinfo);
                fwrite($this->_stream, $data);
                foreach($this->_parts as $bp) {
                    if (is_resource($bp)) {
                        rewind($bp);
                        stream_copy_to_stream($bp, $this->_stream);
                        fclose($bp);
                    } else {
                        fwrite($this->_stream, $bp);
                    }
                }
            }
        }
    }

    /**
     * Output the tag content
     *
     * @param mixed $content  The value to output for this tag. A string or
     *                        a stream resource.
     */
    public function content($content)
    {
        // Don't try to send a string containing \0 - it's the wbxml string
        // terminator.
        if (!is_resource($content)) {
            $content = str_replace("\0", '', $content);
            if ('x' . $content == 'x') {
                return;
            }
        } else {
            stream_filter_register('horde_null', Horde_Stream_Filter_Null);
            $filter = stream_filter_prepend($content, 'horde_null', STREAM_FILTER_READ);
        }
        $this->_outputStack();
        $this->_content($content);
        if (is_resource($content)) {
            fclose($content);
        }
        if (isset($filter)) {
            stream_filter_remove($filter);
        }
    }

    /**
     * Add a mulitpart part to be output.
     *
     * @param mixed $data  The part data. A string or stream resource.
     */
    public function addPart($data)
    {
        $this->_parts[] = $data;
    }

    /**
     * Return the parts array.
     *
     * @return array
     */
    public function getParts()
    {
        return $this->_parts;
    }

    /**
     * Output any tags on the stack that haven't been output yet
     *
     */
    private function _outputStack()
    {
        for ($i=0; $i < count($this->_stack); $i++) {
            if (!$this->_stack[$i]['sent']) {
                $this->_startTag(
                    $this->_stack[$i]['tag'],
                    $this->_stack[$i]['attributes'],
                    $this->_stack[$i]['nocontent']);
                $this->_stack[$i]['sent'] = true;
            }
        }
    }

    /**
     * Actually outputs the start tag
     *
     * @param string $tag @see Horde_ActiveSync_Wbxml_Encoder::startTag
     * @param mixed $attributes @see Horde_ActiveSync_Wbxml_Encoder::startTag
     * @param boolean $output_empty @see Horde_ActiveSync_Wbxml_Encoder::startTag
     */
    private function _startTag($tag, $attributes = false, $output_empty = false)
    {
        $this->_logStartTag($tag, $attributes, $output_empty);
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
        } elseif (!isset($output_empty) || !$output_empty) {
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
     * @param mixed $content  A string or stream resource to write to the output
     */
    private function _content($content)
    {
        if (!is_resource($content)) {
            $this->_logContent($content);
        } else {
            $this->_logContent('[STREAM]');
        }
        $this->_outByte(Horde_ActiveSync_Wbxml::STR_I);
        $this->_outTermStr($content);
    }

    /**
     * Output the endtag
     *
     */
    private function _endTag() {
        $this->_logEndTag();
        $this->_outByte(Horde_ActiveSync_Wbxml::END);
    }

    /**
     * Output a single byte to the stream
     *
     * @param byte $byte  The byte to output.
     */
    private function _outByte($byte)
    {
        fwrite($this->_stream, chr($byte));
    }

    /**
     * Outputs an MBUInt to the stream
     *
     * @param $uint  The data to write.
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
     * @param mixed $content  A string or a stream resource.
     */
    private function _outTermStr($content)
    {
        if (is_resource($content)) {
            rewind($content);
            stream_copy_to_stream($content, $this->_stream);
        } else {
            fwrite($this->_stream, $content);
        }
        fwrite($this->_stream, chr(0));
    }

    /**
     * Output attributes
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
     * Switch code page.
     *
     * @param integer $page  The code page to switch to.
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
     * @param boolean $output_empty
     *
     * @return void
     */
    private function _logStartTag($tag, $attr, $output_empty)
    {
        $spaces = str_repeat(' ', count($this->_logStack));
        if ($output_empty) {
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
        $spaces = str_repeat(' ', count($this->_logStack) - 1);
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
        $spaces = str_repeat(' ', count($this->_logStack) + 1);
        $this->_logger->debug('O ' . $spaces . $content);
    }

}