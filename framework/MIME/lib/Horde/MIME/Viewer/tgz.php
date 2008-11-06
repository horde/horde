<?php

require_once 'Horde/Compress.php';
require_once 'Horde/Text.php';

/**
 * The Horde_MIME_Viewer_tgz class renders out plain or gzipped tarballs in
 * HTML.
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Michael Cochrane <mike@graftonhall.co.nz>
 * @package Horde_MIME_Viewer
 */
class Horde_MIME_Viewer_tgz extends Horde_MIME_Viewer_Driver
{
    /**
     * Render out the currently set tar file contents.
     *
     * @param array $params  Any parameters the Viewer may need.
     *
     * @return string  The rendered contents.
     */
    public function render($params = array())
    {
        $contents = $this->mime_part->getContents();

        /* Only decompress gzipped files. */
        $subtype = $this->mime_part->getSubType();
        if (($subtype == 'x-compressed-tar') ||
            ($subtype == 'tgz') ||
            ($subtype == 'x-tgz') ||
            ($subtype == 'gzip') ||
            ($subtype == 'x-gzip') ||
            ($subtype == 'x-gzip-compressed') ||
            ($subtype == 'x-gtar')) {
            $gzip = &Horde_Compress::singleton('gzip');
            $contents = $gzip->decompress($contents);
            if (empty($contents)) {
                return _("Unable to open compressed archive.");
            } elseif (is_a($contents, 'PEAR_Error')) {
                return $contents->getMessage();
            }
        }

        if ($subtype == 'gzip' ||
            $subtype == 'x-gzip' ||
            $subtype == 'x-gzip-compressed') {
            global $conf;
            require_once 'Horde/MIME/Magic.php';
            $mime_type = MIME_Magic::analyzeData($contents, isset($conf['mime']['magic_db']) ? $conf['mime']['magic_db'] : null);
            if (!$mime_type) {
                $mime_type = _("Unknown");
            }
            return sprintf(_("Content type of compressed file: %s"), $mime_type);
        }

        /* Obtain the list of files/data in the tar file. */
        $tar = Horde_Compress::factory('tar');
        $tarData = $tar->decompress($contents);
        if (is_a($tarData, 'PEAR_Error')) {
            return $tarData->getMessage();
        }

        $fileCount = count($tarData);
        $text = '<strong>' . htmlspecialchars(sprintf(_("Contents of \"%s\""), $this->mime_part->getName())) . ':</strong>' . "\n" .
            '<table><tr><td align="left"><tt><span class="fixed">' .
            Text::htmlAllSpaces(_("Archive Name") . ':  ' . $this->mime_part->getName()) . "\n" .
            Text::htmlAllSpaces(_("Archive File Size") . ': ' . strlen($contents) . ' bytes') . "\n" .
            Text::htmlAllSpaces(sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount)) .
            "\n\n" .
            Text::htmlAllSpaces(
                str_pad(_("File Name"),     62, ' ', STR_PAD_RIGHT) .
                str_pad(_("Attributes"),    15, ' ', STR_PAD_LEFT) .
                str_pad(_("Size"),          10, ' ', STR_PAD_LEFT) .
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

        return nl2br($text . str_repeat('-', 106) . "\n" .
                     '</span></tt></td></tr></table>');
    }

    /**
     * Return the content-type
     *
     * @return string  The content-type of the output.
     */
    public function getType()
    {
        return 'text/html; charset=' . NLS::getCharset();
    }
}
