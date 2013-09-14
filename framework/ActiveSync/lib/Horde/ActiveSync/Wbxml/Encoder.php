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
 *   © Zarafa Deutschland GmbH, www.zarafaserver.de
 *   This file is distributed under GPL-2.0.
 *   Consult COPYING file for details
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
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
 * @copyright 2009-2013 Horde LLC (http://www.horde.org)
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
     * Collection of parts to send in MULTIPART responses.
     *
     * @var array
     */
    protected $_parts = array();

    /**
     * Private stream when handling multipart output
     *
     * @var resource
     */
    protected $_tempStream;

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
        if ($multipart) {
            $this->_tempStream = $this->_stream;
            $this->_stream = fopen('php://temp', 'r+');
        }
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
            $stackelem['sent'] = false;
            $this->_stack[] = $stackelem;
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
                rewind($this->_stream);
                $stat = fstat($this->_stream);
                $len = $stat['size'];

                $totalCount = count($this->_parts) + 1;
                $header = pack('i', $totalCount);
                $offset = (($totalCount * 2) * 4) + 4;
                $header .= pack('ii', $offset, $len);
                $offset += $len;

                // start/length of parts
                foreach ($this->_parts as $bp) {
                    if (is_resource($bp)) {
                        rewind($bp);
                        $stat = fstat($bp);
                        $len = $stat['size'];
                    } else {
                        $len = strlen(bin2hex($bp)) / 2;
                    }
                    $header .= pack('ii', $offset, $len);
                    $offset += $len;
                }

                // Output
                fwrite($this->_tempStream, $header);
                rewind($this->_stream);
                while (!feof($this->_stream)) {
                    fwrite($this->_tempStream, fread($this->_stream, 8192));
                }
                foreach($this->_parts as $bp) {
                    if (is_resource($bp)) {
                        rewind($bp);
                        while (!feof($bp)) {
                            fwrite($this->_tempStream, fread($bp, 8192));
                        }
                        fclose($bp);
                    } else {
                        fwrite($this->_tempStream, $bp);
                    }
                }
                fclose($this->_stream);
                $this->_stream = $this->_tempStream;
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
        }
        $this->_outputStack();
        $this->_content($content);

        if (is_resource($content)) {
            fclose($content);
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
        for ($i = 0; $i < count($this->_stack); $i++) {
            if (!$this->_stack[$i]['sent']) {
                $this->_startTag(
                    $this->_stack[$i]['tag'],
                    $this->_stack[$i]['attributes']);
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
        } elseif (!$output_empty) {
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
            if ($this->_logLevel == self::LOG_PROTOCOL &&
                ($l = Horde_String::length($content)) > self::LOG_MAXCONTENT) {
                $this->_logContent(sprintf('[%d bytes of content]', $l));
            } else {
                $this->_logContent($content);
            }
        } else {
            $this->_logContent('[STREAM]');
        }
        $this->_outByte(self::STR_I);
        $this->_outTermStr($content);
    }

    /**
     * Output the endtag
     *
     */
    private function _endTag() {
        $this->_logEndTag();
        $this->_outByte(self::END);
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
            while (!feof($content)) {
                fwrite($this->_stream, fread($content, 8192));
            }
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
        $this->_outByte(self::END);
    }

    /**
     * Switch code page.
     *
     * @param integer $page  The code page to switch to.
     */
    private function _outSwitchPage($page)
    {
        $this->_outByte(self::SWITCH_PAGE);
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
            $this->_logger->debug(sprintf(
                '[%s] O %s <%s/>',
                $this->_procid,
                $spaces,
                $tag));
        } else {
            $this->_logStack[] = $tag;
            $this->_logger->debug(sprintf(
                '[%s] O %s <%s>',
                $this->_procid,
                $spaces,
                $tag));
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
        $this->_logger->debug(sprintf(
            '[%s] O %s </%s>',
            $this->_procid,
            $spaces,
            $tag));
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
        $this->_logger->debug(sprintf(
            '[%s] O %s %s',
            $this->_procid,
            $spaces,
            $content));
    }

}