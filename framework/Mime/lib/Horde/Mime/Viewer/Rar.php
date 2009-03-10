<?php
/**
 * The Horde_Mime_Viewer_Rar class renders out the contents of .rar archives
 * in HTML format.
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Rar extends Horde_Mime_Viewer_Driver
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
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $ret = $this->_renderInline();
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
        $contents = $this->_mimepart->getContents();

        $rar = &Horde_Compress::singleton('rar');

        $rarData = $rar->decompress($contents);
        if (is_a($rarData, 'PEAR_Error')) {
            return array();
        }
        $fileCount = count($rarData);

        require_once 'Horde/Text.php';

        $name = $this->_mimepart->getName(true);
        if (empty($name)) {
            $name = _("unnamed");
        }

        $text = '<strong>' . htmlspecialchars(sprintf(_("Contents of \"%s\""), $name)) . ':</strong>' . "\n" .
            '<table><tr><td align="left"><tt><span class="fixed">' .
            Text::htmlAllSpaces(_("Archive Name") . ':  ' . $name) . "\n" .
            Text::htmlAllSpaces(_("Archive File Size") . ': ' . strlen($contents) . ' bytes') . "\n" .
            Text::htmlAllSpaces(sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount)) .
            "\n\n" .
            Text::htmlAllSpaces(
                String::pad(_("File Name"), 50, ' ', STR_PAD_RIGHT) .
                String::pad(_("Attributes"), 10, ' ', STR_PAD_LEFT) .
                String::pad(_("Size"), 10, ' ', STR_PAD_LEFT) .
                String::pad(_("Modified Date"), 19, ' ', STR_PAD_LEFT) .
                String::pad(_("Method"), 10, ' ', STR_PAD_LEFT) .
                String::pad(_("Ratio"), 10, ' ', STR_PAD_LEFT)
            ) . "\n" .
            str_repeat('-', 109) . "\n";

        foreach ($rarData as $val) {
            $ratio = empty($val['size'])
                ? 0
                : 100 * ($val['csize'] / $val['size']);

            $text .= Text::htmlAllSpaces(
                String::pad($val['name'], 50, ' ', STR_PAD_RIGHT) .
                String::pad($val['attr'], 10, ' ', STR_PAD_LEFT) .
                String::pad($val['size'], 10, ' ', STR_PAD_LEFT) .
                String::pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT) .
                String::pad($val['method'], 10, ' ', STR_PAD_LEFT) .
                String::pad(sprintf("%1.1f%%", $ratio), 10, ' ', STR_PAD_LEFT)
            ) . "\n";
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => nl2br($text . str_repeat('-', 106) . "\n" . '</span></tt></td></tr></table>'),
                'status' => array(),
                'type' => 'text/html; charset=' . NLS::getCharset()
            )
        );
    }
}
