<?php
/**
 * The Horde_Mime_Viewer_Tgz class renders out plain or gzipped tarballs in
 * HTML.
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
class Horde_Mime_Viewer_Tgz extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => false,
        'info' => true,
        'inline' => false,
        'raw' => false
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
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'gzip' - (Horde_Compress_Gzip) A gzip object.
     * 'monospace' - (string) A class to use to display monospace text inline.
     *               DEFAULT: Uses style="font-family:monospace"
     * 'tar' - (Horde_Compress_Tar) A tar object.
     * </pre>
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);

        $this->_metadata['compressed'] = in_array($part->getSubType(), $this->_gzipSubtypes);
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        /* Currently, can't do anything without tar file. */
        $subtype = $this->_mimepart->getSubType();
        if (in_array($subtype, array('gzip', 'x-gzip', 'x-gzip-compressed'))) {
            return array();
        }

        $charset = $this->getConfigParam('charset');
        $contents = $this->_mimepart->getContents();

        /* Decompress gzipped files. */
        if (in_array($subtype, $this->_gzipSubtypes)) {
            if (!$this->getConfigParam('gzip')) {
                $this->setConfigParam('gzip', Horde_Compress::factory('Gzip'));
            }
            $contents = $this->getConfigParam('gzip')->decompress($contents);
        }

        /* Obtain the list of files/data in the tar file. */
        if (!$this->getConfigParam('tar')) {
            $this->setConfigParam('tar', Horde_Compress::factory('Tar'));
        }
        $tarData = $this->getConfigParam('tar')->decompress($contents);

        $fileCount = count($tarData);

        $name = $this->_mimepart->getName(true);
        if (empty($name)) {
            $name = Horde_Mime_Viewer_Translation::t("unnamed");
        }

        $monospace = $this->getConfigParam('monospace');

        $text = '<table><tr><td align="left"><span ' .
            ($monospace ? 'class="' . $monospace . '">' : 'style="font-family:monospace">') .
            $this->_textFilter(Horde_Mime_Viewer_Translation::t("Archive Name") . ':  ' . $name, 'Space2html', array(
                'charset' => $charset,
                'encode' => true,
                'encode_all' => true
            )) . "\n" .
            $this->_textFilter(Horde_Mime_Viewer_Translation::t("Archive File Size") . ': ' . strlen($contents) . ' bytes', 'Space2html', array(
                'charset' => $charset,
                'encode' => true,
                'encode_all' => true
            )) . "\n" .
            $this->_textFilter(sprintf(Horde_Mime_Viewer_Translation::ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount), 'Space2html', array(
                'charset' => $charset,
                'encode' => true,
                'encode_all' => true
            )) .
            "\n\n" .
            $this->_textFilter(
                str_pad(Horde_Mime_Viewer_Translation::t("File Name"), 62, ' ', STR_PAD_RIGHT) .
                str_pad(Horde_Mime_Viewer_Translation::t("Attributes"), 15, ' ', STR_PAD_LEFT) .
                str_pad(Horde_Mime_Viewer_Translation::t("Size"), 10, ' ', STR_PAD_LEFT) .
                str_pad(Horde_Mime_Viewer_Translation::t("Modified Date"), 19, ' ', STR_PAD_LEFT),
                'Space2html',
                array(
                    'charset' => $charset,
                    'encode' => true,
                    'encode_all' => true
                )
            ) . "\n" .
            str_repeat('-', 106) . "\n";

        foreach ($tarData as $val) {
            $text .= $this->_textFilter(
                str_pad($val['name'], 62, ' ', STR_PAD_RIGHT) .
                str_pad($val['attr'], 15, ' ', STR_PAD_LEFT) .
                str_pad($val['size'], 10, ' ', STR_PAD_LEFT) .
                str_pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT),
                'Space2html',
                array(
                    'charset' => $charset,
                    'encode' => true,
                    'encode_all' => true
                )
            ) . "\n";
        }

        return $this->_renderReturn(
            nl2br($text . str_repeat('-', 106) . "\n</span></td></tr></table>"),
            'text/html; charset=' . $charset
        );
    }

}
