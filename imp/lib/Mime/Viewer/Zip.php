<?php
/**
 * The IMP_Horde_Mime_Viewer_Zip class renders out the contents of ZIP files
 * in HTML format and allows downloading of extractable files.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Mike Cochrane <mike@graftonhall.co.nz>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Horde_Mime_Viewer_Zip extends Horde_Mime_Viewer_Zip
{
    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     * <pre>
     * 'zip_attachment' - (integer) The ZIP attachment to download.
     * </pre>
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        if (!Horde_Util::getFormData('zip_attachment')) {
            $this->_callback = array(&$this, '_IMPcallback');
            return parent::_render();
        }

        /* Send the requested file. Its position in the zip archive is located
         * in 'zip_attachment'. */
        $data = $this->_mimepart->getContents();
        $zip = Horde_Compress::factory('zip');
        $fileKey = Horde_Util::getFormData('zip_attachment') - 1;
        $zipInfo = $zip->decompress($data, array('action' => Horde_Compress_Zip::ZIP_LIST));

        /* Verify that the requested file exists. */
        if (isset($zipInfo[$fileKey])) {
            $text = $zip->decompress($data, array('action' => Horde_Compress_Zip::ZIP_DATA, 'info' => $zipInfo, 'key' => $fileKey));
            if (!empty($text)) {
                return array(
                    $this->_mimepart->getMimeId() => array(
                        'data' => $text,
                        'name' => basename($zipInfo[$fileKey]['name']),
                        'status' => array(),
                        'type' => 'application/octet-stream'
                    )
                );
            }
        }

        // TODO: Error reporting
        return array();
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        $this->_callback = array(&$this, '_IMPcallback');
        return parent::_renderInfo();
    }

    /**
     * The function to use as a callback to _toHTML().
     *
     * @param integer $key  The position of the file in the zip archive.
     * @param array $val    The information array for the archived file.
     *
     * @return string  The content-type of the output.
     */
    protected function _IMPcallback($key, $val)
    {
        $name = preg_replace('/(&nbsp;)+$/', '', $val['name']);

        if (!empty($val['size']) && (strstr($val['attr'], 'D') === false) &&
            ((($val['method'] == 0x8) && Horde_Util::extensionExists('zlib')) ||
             ($val['method'] == 0x0))) {
            $mime_part = $this->_mimepart;
            $mime_part->setName(basename($name));
            $val['name'] = str_replace($name, $this->_params['contents']->linkView($mime_part, 'download_render', $name, array('jstext' => sprintf(_("View %s"), str_replace('&nbsp;', ' ', $name)), 'class' => 'fixed', 'params' => array('zip_attachment' => urlencode($key) + 1))), $val['name']);
        }

        return $val;
    }

}
