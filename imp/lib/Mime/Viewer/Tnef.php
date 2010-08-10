<?php
/**
 * The IMP_Mime_Viewer_Tnef class allows MS-TNEF attachments to be
 * displayed.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Tnef extends Horde_Mime_Viewer_Tnef
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
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
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * URL parameters used by this function:
     * <pre>
     * 'tnef_attachment' - (integer) The TNEF attachment to download.
     * </pre>
     *
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        if (!($tnef_atc = Horde_Util::getFormData('tnef_attachment'))) {
            $ret = $this->_renderInfo();
            reset($ret);
            $ret[key($ret)]['data'] = '<html><body>' . $ret[key($ret)]['data'] . '</body></html>';
            return $ret;
        }

        /* Get the data from the attachment. */
        if (!($tnef = $this->getConfigParam('tnef'))) {
            $tnef = Horde_Compress::factory('Tnef');
            $this->setConfigParam('tnef', $tnef);
        }
        $tnefData = $tnef->decompress($this->_mimepart->getContents());

        /* Display the requested file. Its position in the $tnefData
         * array can be found in 'tnef_attachment'. */
        $tnefKey = $tnef_atc - 1;

        /* Verify that the requested file exists. */
        if (isset($tnefData[$tnefKey])) {
            $text = $tnefData[$tnefKey]['stream'];
            if (!empty($text)) {
                return array(
                    $this->_mimepart->getMimeId() => array(
                        'data' => $text,
                        'name' => $tnefData[$tnefKey]['name'],
                        'status' => array(),
                        'type' => $tnefData[$tnefKey]['type'] . '/' . $tnefData[$tnefKey]['subtype']
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
     * @return array  See parent::render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        /* Get the data from the attachment. */
        if (!($tnef = $this->getConfigParam('tnef'))) {
            $tnef = Horde_Compress::factory('Tnef');
            $this->setConfigParam('tnef', $tnef);
        }
        $tnefData = $tnef->decompress($this->_mimepart->getContents());

        if (!count($tnefData)) {
            /* Ignore attachment if it doesn't contain any files. */
            return array(
                $this->_mimepart->getMimeId() => null
            );
        }

        $text = '';

        reset($tnefData);
        while (list($key, $data) = each($tnefData)) {
            $temp_part = $this->_mimepart;
            $temp_part->setName($data['name']);
            $temp_part->setDescription($data['name']);

            /* Short-circuit MIME-type guessing for winmail.dat parts;
             * we're showing enough entries for them already. */
            $type = $data['type'] . '/' . $data['subtype'];
            if (in_array($type, array('application/octet-stream', 'application/base64'))) {
                $type = Horde_Mime_Magic::filenameToMIME($data['name']);
            }
            $temp_part->setType($type);

            $link = $this->getConfigParam('imp_contents')->linkView($temp_part, 'view_attach', htmlspecialchars($data['name']), array('jstext' => sprintf(_("View %s"), $data['name']), 'params' => array('tnef_attachment' => $key + 1)));
            $text .= _("Attached File:") . '&nbsp;&nbsp;' . $link . '&nbsp;&nbsp;(' . $data['type'] . '/' . $data['subtype'] . ")<br />\n";
        }

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $text,
                'status' => array(
                    array(
                        'text' => array(_("The following files were attached to this part:"))
                    )
                ),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            )
        );
    }

}
