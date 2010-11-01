<?php
/**
 * The Horde_Mime_Viewer_Ooo class renders out OpenOffice.org documents in
 * HTML format.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Marko Djukic <marko@oblo.com>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Ooo extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => false,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        /* At this point assume that the document takes advantage of ZIP
         * compression. */
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
     * 'zip' - (Horde_Compress_Zip) A zip object.
     * </pre>
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        parent::__construct($part, $conf);
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        $has_xslt = Horde_Util::extensionExists('xslt');
        $has_ssfile = function_exists('domxml_xslt_stylesheet_file');
        if (($use_xslt = $has_xslt || $has_ssfile)) {
            $tmpdir = Horde_Util::createTempDir(true);
        }

        $fnames = array('content.xml', 'styles.xml', 'meta.xml');
        $tags = array(
            'text:p' => 'p',
            'table:table' => 'table border="0" cellspacing="1" cellpadding="0" ',
            'table:table-row' => 'tr bgcolor="#cccccc"',
            'table:table-cell' => 'td',
            'table:number-columns-spanned=' => 'colspan='
        );

        if (!$this->getConfigParam('zip')) {
            $this->setConfigParam('zip', Horde_Compress::factory('Zip'));
        }
        $list = $this->getConfigParam('zip')->decompress($this->_mimepart->getContents(), array('action' => Horde_Compress_Zip::ZIP_LIST));

        foreach ($list as $key => $file) {
            if (in_array($file['name'], $fnames)) {
                $content = $zip->decompress($this->_mimepart->getContents(), array(
                    'action' => Horde_Compress_Zip::ZIP_DATA,
                    'info' => $list,
                    'key' => $key
                ));

                if ($use_xslt) {
                    file_put_contents($tmpdir . $file['name'], $content);
                } elseif ($file['name'] == 'content.xml') {
                    return array(
                        $this->_mimepart->getMimeId() => array(
                            'data' => str_replace(array_keys($tags), array_values($tags), $content),
                            'status' => array(),
                            'type' => 'text/html; charset=UTF-8'
                        )
                    );
                }
            }
        }

        if (!Horde_Util::extensionExists('xslt')) {
            return array();
        }

        $xsl_file = dirname(__FILE__) . '/Ooo/main_html.xsl';

        if ($has_ssfile) {
            /* Use DOMXML */
            $xslt = domxml_xslt_stylesheet_file($xsl_file);
            $dom  = domxml_open_file($tmpdir . 'content.xml');
            $result = @$xslt->process($dom, array(
                'metaFileURL' => $tmpdir . 'meta.xml',
                'stylesFileURL' => $tmpdir . 'styles.xml',
                'disableJava' => true
            ));
            $result = $xslt->result_dump_mem($result);
        } else {
            // Use XSLT
            $xslt = xslt_create();
            $result = @xslt_process($xslt, $tmpdir . 'content.xml', $xsl_file, null, null, array(
                'metaFileURL' => $tmpdir . 'meta.xml',
                'stylesFileURL' => $tmpdir . 'styles.xml',
                'disableJava' => true
            ));
            if (!$result) {
                $result = xslt_error($xslt);
            }
            xslt_free($xslt);
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $result,
                'status' => array(),
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

}
