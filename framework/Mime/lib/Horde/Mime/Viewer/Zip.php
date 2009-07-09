<?php
/**
 * The Horde_Mime_Viewer_Zip class renders out the contents of ZIP files in
 * HTML format.
 *
 * Copyright 2000-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Zip extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => true,
        'full' => true,
        'info' => false,
        'inline' => true
    );

    /**
     * A callback function to use in _toHTML().
     *
     * @var callback
     */
    protected $_callback = null;

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $ret = $this->_toHTML();
        if (!empty($ret)) {
            reset($ret);
            $ret[key($ret)]['data'] = '<html><body>' . $ret[key($ret)]['data'] . '</body></html>';
        }
        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        return $this->_toHTML();
    }

    /**
     * Converts the ZIP file to an HTML display.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _toHTML()
    {
        $contents = $this->_mimepart->getContents();

        $zip = &Horde_Compress::singleton('zip');

        /* Make sure this is a valid zip file. */
        if ($zip->checkZipData($contents) === false) {
            return array();
        }

        $zipInfo = $zip->decompress($contents, array('action' => HORDE_COMPRESS_ZIP_LIST));
        if (is_a($zipInfo, 'PEAR_Error')) {
            return array();
        }
        $fileCount = count($zipInfo);

        /* Determine maximum file name length. */
        $max_array = array();
        foreach ($zipInfo as $val) {
            $max_array[] = strlen($val['name']);
        }
        $maxlen = empty($max_array) ? 0 : max($max_array);

        $name = $this->_mimepart->getName(true);
        if (empty($name)) {
            $name = _("unnamed");
        }

        $text = '<strong>' . htmlspecialchars(sprintf(_("Contents of \"%s\""), $name)) . ':</strong>' . "\n" .
            '<table><tr><td align="left"><tt><span class="fixed">' .
            Horde_Text_Filter::filter(
                _("Archive Name") . ': ' . $name . "\n" .
                _("Archive File Size") . ': ' . strlen($contents) .
                ' bytes' . "\n" .
                sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount) .
                "\n\n" .
                Horde_String::pad(_("File Name"), $maxlen, ' ', STR_PAD_RIGHT) .
                Horde_String::pad(_("Attributes"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Size"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Modified Date"), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Method"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("CRC"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Ratio"), 10, ' ', STR_PAD_LEFT) .
                "\n",
                'space2html',
                array('charset' => Horde_Nls::getCharset(), 'encode' => true, 'encode_all' => true)
            ) . str_repeat('-', 69 + $maxlen) . "\n";

        foreach ($zipInfo as $key => $val) {
            $ratio = (empty($val['size']))
                ? 0
                : 100 * ($val['csize'] / $val['size']);

            $val['name']   = Horde_String::pad($val['name'], $maxlen, ' ', STR_PAD_RIGHT);
            $val['attr']   = Horde_String::pad($val['attr'], 10,' ', STR_PAD_LEFT);
            $val['size']   = Horde_String::pad($val['size'], 10, ' ', STR_PAD_LEFT);
            $val['date']   = Horde_String::pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT);
            $val['method'] = Horde_String::pad($val['method'], 10, ' ', STR_PAD_LEFT);
            $val['crc']    = Horde_String::pad($val['crc'], 10, ' ', STR_PAD_LEFT);
            $val['ratio']  = Horde_String::pad(sprintf("%1.1f%%", $ratio), 10, ' ', STR_PAD_LEFT);

            $val = array_map(array('Text', 'htmlAllSpaces'), $val);
            if (!is_null($this->_callback)) {
                $val = call_user_func($this->_callback, $key, $val);
            }

            $text .= $val['name'] . $val['attr'] . $val['size'] .
                $val['date'] . $val['method'] . $val['crc'] . $val['ratio'] .
                "\n";
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => nl2br($text . str_repeat('-', 69 + $maxlen) . "\n" . '</span></tt></td></tr></table>'),
                'status' => array(),
                'type' => 'text/html; charset=' . Horde_Nls::getCharset()
            )
        );
    }
}
