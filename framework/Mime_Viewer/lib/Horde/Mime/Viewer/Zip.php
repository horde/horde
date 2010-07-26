<?php
/**
 * The Horde_Mime_Viewer_Zip class renders out the contents of ZIP files in
 * HTML format.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Michael Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Zip extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => true,
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => true,
        'embedded' => false,
        'forceinline' => false
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
     * @throws Horde_Exception
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
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        return $this->_toHTML();
    }

    /**
     * Converts the ZIP file to an HTML display.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     * @throws Horde_Exception
     */
    protected function _toHTML()
    {
        $contents = $this->_mimepart->getContents();

        $zip = Horde_Compress::factory('zip');
        $zipInfo = $zip->decompress($contents, array('action' => Horde_Compress_Zip::ZIP_LIST));

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

        $text = '<strong>' . htmlspecialchars(sprintf(_("Contents of \"%s\""), $name)) . ":</strong>\n" .
            '<table><tr><td align="left"><span style="font-family:monospace">' .
            Horde_Text_Filter::filter(
                _("Archive Name") . ': ' . $name . "\n" .
                _("Archive File Size") . ': ' . strlen($contents) .
                " bytes\n" .
                sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount) .
                "\n\n" .
                Horde_String::pad(_("File Name"), $maxlen, ' ', STR_PAD_RIGHT) .
                Horde_String::pad(_("Attributes"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Size"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Modified Date"), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Method"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Ratio"), 10, ' ', STR_PAD_LEFT) .
                "\n",
                'space2html',
                array('charset' => $GLOBALS['registry']->getCharset(), 'encode' => true, 'encode_all' => true)
            ) . str_repeat('-', 59 + $maxlen) . "\n";

        foreach ($zipInfo as $key => $val) {
            $ratio = (empty($val['size']))
                ? 0
                : 100 * ($val['csize'] / $val['size']);

            $val['name']   = Horde_String::pad($val['name'], $maxlen, ' ', STR_PAD_RIGHT);
            $val['attr']   = Horde_String::pad($val['attr'], 10, ' ', STR_PAD_LEFT);
            $val['size']   = Horde_String::pad($val['size'], 10, ' ', STR_PAD_LEFT);
            $val['date']   = Horde_String::pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT);
            $val['method'] = Horde_String::pad($val['method'], 10, ' ', STR_PAD_LEFT);
            $val['ratio']  = Horde_String::pad(sprintf("%1.1f%%", $ratio), 10, ' ', STR_PAD_LEFT);

            reset($val);
            while (list($k, $v) = each($val)) {
                $val[$k] = Horde_Text_Filter::filter($v, 'space2html', array('charset' => $GLOBALS['registry']->getCharset(), 'encode' => true, 'encode_all' => true));
            }

            if (!is_null($this->_callback)) {
                $val = call_user_func($this->_callback, $key, $val);
            }

            $text .= $val['name'] . $val['attr'] . $val['size'] .
                $val['date'] . $val['method'] . $val['ratio'] .
                "\n";
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => nl2br($text . str_repeat('-', 59 + $maxlen) . "\n</span></td></tr></table>"),
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }
}
