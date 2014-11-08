<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handler to render the contents of gzip/tar files, allowing downloading of
 * extractable files.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Tgz extends Horde_Mime_Viewer_Tgz
{
    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - tgz_attachment: (integer) The ZIP attachment to download.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        global $injector;

        $vars = $injector->getInstance('Horde_Variables');
        $tgzInfo = $this->_getTgzInfo();

        /* Verify that the requested file exists. */
        if ((($key = $vars->tgz_attachment) === null) ||
            !isset($tgzInfo[$key])) {
            return array();
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $tgzInfo[$key]['data'],
                'name' => basename($tgzInfo[$key]['name']),
                'type' => 'application/octet-stream'
            )
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - tgz_contents: (integer) If set, show contents of ZIP file.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        global $injector;

        $vars = $injector->getInstance('Horde_Variables');

        if (!$this->getConfigParam('show_contents') &&
            !isset($vars->tgz_contents)) {
            $status = new IMP_Mime_Status(_("This is a gzip/tar compressed file."));
            $status->icon('mime/compressed.png');
            $status->addText(
                Horde::link('#', '', 'tgzViewContents') .
                _("Click HERE to display the contents.") .
                '</a>'
            );

            return array(
                $this->_mimepart->getMimeId() => array(
                    'data' => '',
                    'status' => $status,
                    'type' => 'text/html; charset=UTF-8'
                )
            );
        }

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/mime'
        ));
        $view->addHelper('Text');

        $view->files = array();

        $tgzInfo = $this->_getTgzInfo();
        $zlib = Horde_Util::extensionExists('zlib');

        foreach ($tgzInfo as $key => $val) {
            if (!strlen($val['data'])) {
                continue;
            }

            $file = new stdClass;
            $file->download = '';
            $file->name = $val['name'];
            $file->size = IMP::sizeFormat($val['size']);

            if (!empty($val['size'])) {
                $file->download = $this->getConfigParam('imp_contents')->linkView(
                    $this->_mimepart,
                    'download_render',
                    '',
                    array(
                        'class' => 'iconImg downloadAtc',
                        'jstext' => _("Download"),
                        'params' => array(
                            'tgz_attachment' => $key
                        )
                    )
                );
            }

            $view->files[] = $file;
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $view->render('tgz'),
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

    /**
     * Return TGZ information.
     *
     * @return array  See Horde_Compress_Tgz#decompress().
     */
    protected function _getTgzInfo()
    {
        $contents = $this->_mimepart->getContents();
        $gzip = $this->getConfigParam('gzip');
        $tar = $this->getConfigParam('tar');

        try {
            $contents = $gzip->decompress($contents);
            $this->_metadata['compressed'] = true;
        } catch (Horde_Compress_Exception $e) {
            $this->_metadata['compressed'] = false;
        }

        try {
            return $tar->decompress($contents);
        } catch (Horde_Compress_Exception $e) {
            if ($this->_metadata['compressed']) {
                /* Doubly gzip'd tgz files are somewhat common. Try a second
                 * decompression before giving up. */
                try {
                    return $tar->decompress(
                        $gzip->decompress($contents)
                    );
                } catch (Horde_Compress_Exception $e) {}
            }
        }

        return null;
    }

}
