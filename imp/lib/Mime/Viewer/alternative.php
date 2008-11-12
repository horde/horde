<?php
/**
 * The IMP_Horde_Mime_Viewer_alternative class renders out messages from
 * multipart/alternative content types (RFC 2046 [5.1.4]).
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_alternative extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => false,
        'info' => false,
        'inline' => true,
    );

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render(). This driver
     *                returns an extra parameter: 'summary_id', which
     *                identifies the MIME ID that should be used for the
     *                summary information.
     */
    protected function _renderInline()
    {
        $display_id = $last_id = null;

        $subparts = $this->_mimepart->contentTypeMap();
        unset($subparts[key($subparts)]);

        /* Look for a displayable part. RFC 2046: show the LAST choice that
         * can be displayed inline. */
        foreach ($subparts as $mime_id => $mime_type) {
            if (is_null($last_id) || (strpos($mime_id, $last_id) !== 0)) {
                $last_id = null;
                $viewer = Horde_Mime_Viewer::factory($mime_type);

                if ($viewer->canRender('inline')) {
                    $mime_part = $this->_params['contents']->getMIMEPart($mime_id, array('nocontents' => true, 'nodecode' => true));
                    if ($mime_part->getDisposition() == 'inline') {
                        $display_id = $last_id = $mime_id;
                    }
                }
            }
        }

        if (is_null($display_id)) {
            return array(
                'status' => array(
                    array(
                        'text' => array(_("There are no alternative parts that can be displayed inline.")),
                        'type' => 'info'
                    )
                )
            );
        }

        $render_data = $this->_params['contents']->renderMIMEPart($display_id, 'inline');

        return array(
            'data' => $render_data['data'],
            'ids' => array_keys($subparts),
            'status' => $render_data['status'],
            'summary_id' => isset($render_data['summary_id']) ? $render_data['summary_id'] : $display_id
        );
    }
}
