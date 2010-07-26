<?php
/**
 * The Horde_Mime_Viewer_Rar class renders out the contents of .rar archives
 * in HTML format.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Michael Cochrane <mike@graftonhall.co.nz>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Rar extends Horde_Mime_Viewer_Base
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
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_renderFullReturn($this->_renderInfo());
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        $contents = $this->_mimepart->getContents();

        $rar = Horde_Compress::factory('rar');
        $rarData = $rar->decompress($contents);

        $charset = $GLOBALS['registry']->getCharset();
        $fileCount = count($rarData);

        $name = $this->_mimepart->getName(true);
        if (empty($name)) {
            $name = _("unnamed");
        }

        $text = '<strong>' . htmlspecialchars(sprintf(_("Contents of \"%s\""), $name)) . ":</strong>\n" .
            '<table><tr><td align="left"><pre>' .
            Horde_Text_Filter::filter(_("Archive Name") . ':  ' . $name, 'space2html', array('charset' => $charset, 'encode' => true, 'encode_all' => true)) . "\n" .
            Horde_Text_Filter::filter(_("Archive File Size") . ': ' . strlen($contents) . ' bytes', 'space2html', array('charset' => $charset, 'encode' => true, 'encode_all' => true)) . "\n" .
            Horde_Text_Filter::filter(sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount), 'space2html', array('charset' => $charset, 'encode' => true, 'encode_all' => true)) .
            "\n\n" .
            Horde_Text_Filter::filter(
                Horde_String::pad(_("File Name"), 50, ' ', STR_PAD_RIGHT) .
                Horde_String::pad(_("Attributes"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Size"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Modified Date"), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Method"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Ratio"), 10, ' ', STR_PAD_LEFT),
                'space2html',
                array('charset' => $charset, 'encode' => true, 'encode_all' => true)) . "\n" .
            str_repeat('-', 109) . "\n";

        foreach ($rarData as $val) {
            $ratio = empty($val['size'])
                ? 0
                : 100 * ($val['csize'] / $val['size']);

            $text .= Horde_Text_Filter::filter(
                Horde_String::pad($val['name'], 50, ' ', STR_PAD_RIGHT) .
                Horde_String::pad($val['attr'], 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad($val['size'], 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad($val['method'], 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(sprintf("%1.1f%%", $ratio), 10, ' ', STR_PAD_LEFT),
                'space2html',
                array('encode' => true, 'encode_all' => true)
            ) . "\n";
        }

        return $this->_renderReturn(
            nl2br($text . str_repeat('-', 106) . "\n</pre></td></tr></table>"),
            'text/html; charset=' . $charset
        );
    }

}
