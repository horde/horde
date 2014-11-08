<?php
/**
 * Copyright 2002-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Handler to render the contents of ZIP files, allowing downloading of
 * extractable files.
 *
 * @author    Mike Cochrane <mike@graftonhall.co.nz>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Zip extends Horde_Mime_Viewer_Zip
{
    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - zip_attachment: (integer) The ZIP attachment to download.
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        global $injector;

        $vars = $injector->getInstance('Horde_Variables');
        $zipInfo = $this->_getZipInfo();

        /* Verify that the requested file exists. */
        if ((($key = $vars->zip_attachment) === null) ||
            !isset($zipInfo[$key])) {
            return array();
        }

        $text = $this->getConfigParam('zip')->decompress(
            $this->_mimepart->getContents(),
            array(
                'action' => Horde_Compress_Zip::ZIP_DATA,
                'info' => $zipInfo,
                'key' => $key
            )
        );

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $text,
                'name' => basename($zipInfo[$key]['name']),
                'type' => 'application/octet-stream'
            )
        );
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     *   - zip_contents: (integer) If set, show contents of ZIP file.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        global $injector;

        $vars = $injector->getInstance('Horde_Variables');

        if (!$this->getConfigParam('show_contents') &&
            !$vars->zip_contents) {
            $status = new IMP_Mime_Status(
                $this->_mimepart,
                _("This is a compressed file.")
            );
            $status->addMimeAction(
                'zipViewContents',
                _("Click HERE to display the file contents.")
            );
            $status->icon('mime/compressed.png');

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

        $view->downloadclass = 'zipdownload';
        $view->files = array();
        $view->tableclass = 'zipcontents';

        $zlib = Horde_Util::extensionExists('zlib');

        foreach ($this->_getZipInfo() as $key => $val) {
            $file = new stdClass;
            $file->name = $val['name'];
            $file->size = IMP::sizeFormat($val['size']);

            /* TODO: Add ability to render in-browser for filetypes we can
             *       handle. */
            if (!empty($val['size']) &&
                (strstr($val['attr'], 'D') === false) &&
                (($zlib && ($val['method'] == 0x8)) ||
                 ($val['method'] == 0x0))) {
                $file->download = $this->getConfigParam('imp_contents')->linkView(
                    $this->_mimepart,
                    'download_render',
                    '',
                    array(
                        'class' => 'iconImg downloadAtc',
                        'jstext' => _("Download"),
                        'params' => array(
                            'zip_attachment' => $key
                        )
                    )
                );
            } else {
                $file->download = '';
            }

            $view->files[] = $file;
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $view->render('compressed'),
                'type' => 'text/html; charset=UTF-8'
            )
        );
    }

    /**
     * Return ZIP information.
     *
     * @return array  See Horde_Compress_Zip#decompress().
     */
    protected function _getZipInfo()
    {
        $data = $this->_mimepart->getContents();
        $zip = $this->getConfigParam('zip');

        return $zip->decompress($data, array(
            'action' => $zip::ZIP_LIST
        ));
    }

}
