<?php
/**
 * The Whups_Mime_Viewer_Zip class renders out the contents of ZIP files
 * in HTML format and allows downloading of extractable files.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Mike Cochrane <mike@graftonhall.co.nz>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_MIME_Viewer
 */
class Whups_Mime_Viewer_zip extends Horde_Mime_Viewer_Zip
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
     */
    protected function _render()
    {
        if (!($zip_atc = Horde_Util::getFormData('zip_attachment'))) {
            $this->_callback = array($this, '_whupsCallback');
            return parent::_render();
        }

        /* Send the requested file. Its position in the zip archive is located
         * in 'zip_attachment'. */
        $data = $this->_mimepart->getContents();

        if (!($zip = $this->getConfigParam('zip'))) {
            $zip = Horde_Compress::factory('zip');
            $this->setConfigParam('zip', $zip);
        }

        $fileKey = $zip_atc - 1;
        $zipInfo = $zip->decompress($data, array(
            'action' => Horde_Compress_Zip::ZIP_LIST
        ));

        /* Verify that the requested file exists. */
        if (isset($zipInfo[$fileKey])) {
            $text = $zip->decompress($data, array(
                'action' => Horde_Compress_Zip::ZIP_DATA,
                'info' => &$zipInfo,
                'key' => $fileKey
            ));
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
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInfo()
    {
        $this->_callback = array($this, '_whupsCallback');
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
    protected function _whupsCallback($key, $val)
    {
        $name = preg_replace('/(&nbsp;)+$/', '', $val['name']);

        if (!empty($val['size']) && (strstr($val['attr'], 'D') === false) &&
            ((($val['method'] == 0x8) && Horde_Util::extensionExists('zlib')) ||
             ($val['method'] == 0x0))) {
            $mime_part = $this->_mimepart;
            $mime_part->setName(basename($name));
            $val['name'] = str_replace($name, Horde::applicationUrl('view.php')->add(array('actionID' => 'view_file', 'type' => Horde_Util::getFormData('type'), 'file' => Horde_Util::getFormData('file'), 'ticket' => Horde_Util::getFormData('ticket'), 'zip_attachment' => $key + 1))->link() . $name . '</a>', $val['name']);
        }

        return $val;
    }

}
