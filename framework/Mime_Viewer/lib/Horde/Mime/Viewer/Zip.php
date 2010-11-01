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
class Horde_Mime_Viewer_Zip extends Horde_Mime_Viewer_Base
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
     * A callback function to use in _toHTML().
     *
     * @var callback
     */
    protected $_callback = null;

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'monospace' - (string) A class to use to display monospace text inline.
     *               DEFAULT: Uses style="font-family:monospace"
     * 'zip' - (Horde_Compress_Zip) Zip object.
     * </pre>
     *
     * @throws InvalidArgumentException
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

        if (!$this->getConfigParam('zip')) {
            $this->setConfigParam('zip', Horde_Compress::factory('Zip'));
        }
        $zipInfo = $this->getConfigParam('zip')->decompress($contents, array(
            'action' => Horde_Compress_Zip::ZIP_LIST
        ));

        $fileCount = count($zipInfo);

        $name = $this->_mimepart->getName(true);
        if (empty($name)) {
            $name = Horde_Mime_Viewer_Translation::t("unnamed");
        }

        $monospace = $this->getConfigParam('monospace');

        $text = '<table><tr><td align="left"><span ' .
            ($monospace ? 'class="' . $monospace . '">' : 'style="font-family:monospace">') .
            $this->_textFilter(
                Horde_Mime_Viewer_Translation::t("Archive Name") . ': ' . $name . "\n" .
                Horde_Mime_Viewer_Translation::t("Archive File Size") . ': ' . strlen($contents) .
                " bytes\n" .
                sprintf(Horde_Mime_Viewer_Translation::ngettext("File Count: %d file", "File Count: %d files", $fileCount), $fileCount) .
                "\n\n" .
                str_repeat(' ', 15) .
                Horde_String::pad(Horde_Mime_Viewer_Translation::t("Attributes"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(Horde_Mime_Viewer_Translation::t("Size"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(Horde_Mime_Viewer_Translation::t("Modified Date"), 19, ' ', STR_PAD_LEFT) .
                Horde_String::pad(Horde_Mime_Viewer_Translation::t("Method"), 10, ' ', STR_PAD_LEFT) .
                Horde_String::pad(Horde_Mime_Viewer_Translation::t("Ratio"), 10, ' ', STR_PAD_LEFT) .
                "\n",
                'Space2html',
                array(
                    'charset' => $charset,
                    'encode' => true,
                    'encode_all' => true
                )
            ) . str_repeat('-', 74) . "\n";

        foreach ($zipInfo as $key => $val) {
            $ratio = (empty($val['size']))
                ? 0
                : 100 * ($val['csize'] / $val['size']);

            $val['name'] = Horde_String::pad(Horde_String::truncate($val['name'], 15), 15, ' ', STR_PAD_RIGHT);
            $val['attr'] = Horde_String::pad($val['attr'], 10, ' ', STR_PAD_LEFT);
            $val['size'] = Horde_String::pad($val['size'], 10, ' ', STR_PAD_LEFT);
            $val['date'] = Horde_String::pad(strftime("%d-%b-%Y %H:%M", $val['date']), 19, ' ', STR_PAD_LEFT);
            $val['method'] = Horde_String::pad($val['method'], 10, ' ', STR_PAD_LEFT);
            $val['ratio'] = Horde_String::pad(sprintf("%1.1f%%", $ratio), 10, ' ', STR_PAD_LEFT);

            reset($val);
            while (list($k, $v) = each($val)) {
                $val[$k] = $this->_textFilter($v, 'Space2html', array(
                    'charset' => $charset,
                    'encode' => true,
                    'encode_all' => true
                ));
            }

            if (!is_null($this->_callback)) {
                $val = call_user_func($this->_callback, $key, $val);
            }

            $text .= $val['name'] . $val['attr'] . $val['size'] .
                $val['date'] . $val['method'] . $val['ratio'] .
                "\n";
        }

        return $this->_renderReturn(
            nl2br($text . str_repeat('-', 74) . "\n</span></td></tr></table>"),
            'text/html; charset=' . $charset
        );
    }

}
