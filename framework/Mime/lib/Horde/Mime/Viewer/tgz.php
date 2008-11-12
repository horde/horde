<?php
/**
 * The Horde_Mime_Viewer_tgz class renders out plain or gzipped tarballs in
 * HTML.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_tgz extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => true,
        'info' => false,
        'inline' => true
    );

    /**
     * The list of compressed subtypes.
     *
     * @var array
     */
    protected $_gzipSubtypes = array(
        'x-compressed-tar', 'tgz', 'x-tgz', 'gzip', 'x-gzip',
        'x-gzip-compressed', 'x-gtar'
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
            $ret['data'] = '<html><body>' . $ret['data'] . '</body></html>';
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

        /* Only decompress gzipped files. */
        $subtype = $this->_mimepart->getSubType();
        if (in_array($subtype, $this->_gzipSubtypes)) {
            $gzip = &Horde_Compress::singleton('gzip');
            $contents = $gzip->decompress($contents);
            if (is_a($contents, 'PEAR_Error') ||
                empty($contents)) {
                return array();
            }
        }

        /* Obtain the list of files/data in the tar file. */
        $tar = &Horde_Compress::singleton('tar');
        $tarData = $tar->decompress($contents);
        if (is_a($tarData, 'PEAR_Error')) {
            return array();
        }
        $fileCount = count($tarData);

        require_once 'Horde/Text.php';

        $text = '<strong>' . htmlspecialchars(sprintf(_("Contents of \"%s\""), $this->_mimepart->getName(true))) . ':</strong>' . "\n" .
            '<table><tr><td align="left"><tt><span class="fixed">' .
            Text::htmlAllSpaces(_("Archive Name") . ':  ' . $this->_mimepart->getName(true)) . "\n" .
            Text::htmlAllSpaces(_("Archive File Size") . ': ' . strlen($contents) . ' bytes') . "\n" .
            Text::htmlAllSpaces(sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount)) .
            "\n\n" .
            Text::htmlAllSpaces(
                str_pad(_("File Name"), 62, ' ', STR_PAD_RIGHT) .
                str_pad(_("Attributes"), 15, ' ', STR_PAD_LEFT) .
                str_pad(_("Size"), 10, ' ', STR_PAD_LEFT) .
                str_pad(_("Modified Date"), 19, ' ', STR_PAD_LEFT)
            ) . "\n" .
            str_repeat('-', 106) . "\n";

        foreach ($tarData as $val) {
            $text .= Text::htmlAllSpaces(
                str_pad($val['name'], 62, ' ', STR_PAD_RIGHT) .
                str_pad($val['attr'], 15, ' ', STR_PAD_LEFT) .
                str_pad($val['size'], 10, ' ', STR_PAD_LEFT) .
                str_pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT)
            ) . "\n";
        }

        return array(
            'data' => nl2br($text . str_repeat('-', 106) . "\n" . '</span></tt></td></tr></table>'),
            'type' => 'text/html; charset=' . NLS::getCharset()
        );
    }
}
