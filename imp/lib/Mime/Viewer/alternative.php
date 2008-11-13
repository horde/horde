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
        $base_id = $this->_mimepart->getMimeId();
        $subparts = $this->_mimepart->contentTypeMap();

        $base_ids = $display_ids = $ret = array();

        /* Look for a displayable part. RFC: show the LAST choice that can be
         * displayed inline. If an alternative is itself a multipart, the user
         * agent is allowed to show that alternative, an earlier alternative,
         * or both. If we find a multipart alternative that contains at least
         * one viewable part, we will display all viewable subparts of that
         * alternative. */
        foreach (array_keys($subparts) as $mime_id) {
            $ret[$mime_id] = null;
            if ((strcmp($base_id, $mime_id) !== 0) &&
                $this->_params['contents']->canDisplay($mime_id, IMP_Contents::RENDER_INLINE)) {
                $display_ids[] = $mime_id;
            }
        }

        /* If we found no IDs, return now. */
        if (empty($display_ids)) {
            $ret[$base_id] = array(
                'data' => '',
                'status' => array(
                    array(
                        'text' => array(_("There are no alternative parts that can be displayed inline.")),
                        'type' => 'info'
                    )
                ),
                'type' => 'text/html; charset=' . NLS::getCharset()
            );
            return $ret;
        }

        /* Get the list of IDs directly under the subpart. */
        reset($subparts);
        next($subparts);
        $id = key($subparts);
        do {
            $base_ids[] = $id;
            $id = Horde_Mime::mimeIdArithmetic($id, 'next');
        } while (isset($subparts[$id]));

        /* If the last viewable message exists in a subpart, back up to the
         * base multipart and display all viewable parts in that multipart.
         * Else, display the single part. */
        $disp_id = end($display_ids);
        while (!is_null($disp_id) && !in_array($disp_id, $base_ids, true)) {
            $disp_id = Horde_Mime::mimeIdArithmetic($disp_id, 'up');
        }

        /* Now grab all keys under this ID. */
        $display_ids = array_flip($display_ids);
        $render_part = $this->_mimepart->getPart($disp_id);
        foreach (array_keys($render_part->contentTypeMap()) as $val) {
            if (isset($display_ids[$val])) {
                $render = $this->_params['contents']->renderMIMEPart($val, IMP_Contents::RENDER_INLINE, array('params' => $this->_params));
                foreach (array_keys($render) as $id) {
                    $ret[$id] = $render[$id];
                    unset($display_ids[$id]);
                }
            }
        }

        return $ret;
    }
}
