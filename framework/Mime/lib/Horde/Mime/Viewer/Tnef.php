<?php
/**
 * The Horde_Mime_Viewer_Tnef class allows MS-TNEF attachments to be
 * displayed.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime
 */
class Horde_Mime_Viewer_Tnef extends Horde_Mime_Viewer_Driver
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
     * @return array  See Horde_Mime_Viewer_Driver::render().
     * @throws Horde_Exception
     */
    protected function _render()
    {
        $ret = $this->_renderInfo();
        if (!empty($ret)) {
            reset($ret);
            $ret[key($ret)]['data'] = '<html><body>' . $ret[key($ret)]['data'] . '</body></html>';
        }
        return $ret;
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     * @throws Horde_Exception
     */
    protected function _renderInfo()
    {
        $tnef = Horde_Compress::factory('tnef');
        $info = $tnef->decompress($this->_mimepart->getContents());

        $data = '<table border="1">';
        if (empty($info)) {
            $data .= '<tr><td>' . _("MS-TNEF Attachment contained no data.") . '</td></tr>';
        } else {
            $data .= '<tr><td>' . _("Name") . '</td><td>' . _("Mime Type") . '</td></tr>';
            foreach ($info as $part) {
                $data .= '<tr><td>' . $part['name'] . '</td><td>' . $part['type'] . '/' . $part['subtype'] . '</td></tr>';
            }
        }
        $data .= '</table>';

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $data,
                'status' => array(),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }

}
