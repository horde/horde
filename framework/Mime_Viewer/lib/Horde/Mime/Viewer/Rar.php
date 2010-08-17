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
        'full' => false,
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
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'monospace' - (string) A class to use to display monospace text inline.
     *               DEFAULT: Uses style="font-family:monospace"
     * 'rar' - (Horde_Compress_Rar) A zip object.
     * </pre>
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        $charset = $this->getConfigParam('charset');
        $contents = $this->_mimepart->getContents();

        if (!$this->getConfigParam('rar')) {
            $this->setConfigParam('rar', Horde_Compress::factory('rar'));
        }
        $rarData = $this->getConfigParam('rar')->decompress($contents);
        $fileCount = count($rarData);

        $name = $this->_mimepart->getName(true);
        if (empty($name)) {
            $name = _("unnamed");
        }

        $monospace = $this->getConfigParam('monospace');

        $text = '<table><tr><td align="left"><span ' .
            ($monospace ? 'class="' . $monospace . '">' : 'style="font-family:monospace">') .
            $this->_textFilter(_("Archive Name") . ':  ' . $name, 'space2html', array(
                'charset' => $charset,
                'encode' => true,
                'encode_all' => true
            )) . "\n" .
            $this->_textFilter(_("Archive File Size") . ': ' . strlen($contents) . ' bytes', 'space2html', array(
                'charset' => $charset,
                'encode' => true,
                'encode_all' => true
            )) . "\n" .
            $this->_textFilter(sprintf(ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount), 'space2html', array(
                'charset' => $charset,
                'encode' => true,
                'encode_all' => true
            )) .
            "\n\n" .
            $this->_textFilter(
                Horde_String::pad(_("File Name"), 50, ' ', STR_PAD_RIGHT) .
                Horde_String::pad(_("Attributes"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Size"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Modified Date"), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Method"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(_("Ratio"), 10, ' ', STR_PAD_LEFT),
                'space2html',
                array(
                    'charset' => $charset,
                    'encode' => true,
                    'encode_all' => true
                )
            ) . "\n" . str_repeat('-', 109) . "\n";

        foreach ($rarData as $val) {
            $ratio = empty($val['size'])
                ? 0
                : 100 * ($val['csize'] / $val['size']);

            $text .= $this->_textFilter(
                Horde_String::pad($val['name'], 50, ' ', STR_PAD_RIGHT) .
                Horde_String::pad($val['attr'], 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad($val['size'], 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad($val['method'], 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(sprintf("%1.1f%%", $ratio), 10, ' ', STR_PAD_LEFT),
                'space2html',
                array(
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
