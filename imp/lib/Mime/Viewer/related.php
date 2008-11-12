<?php
/**
 * The IMP_Horde_Mime_Viewer_related class handles multipart/related
 * (RFC 2387) messages.
 *
 * Copyright 2002-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_related extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => true,
        'info' => true,
        'inline' => true,
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        return $this->_IMPrender(false);
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $ret = $this->_IMPrender(true);
        return empty($ret['ids']) ? $this->_renderInfo() : $ret;
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        return array(
            'status' => array(
                array (
                    'text' => array(sprintf(_("Click %s to view this multipart/related part in a separate window."), $contents->linkViewJS($this->mime_part, 'view_attach', _("HERE"), _("View content in a separate window")))),
                    'icon' => Horde::img('mime/html.png', _("HTML"))
                )
            )
        );
    }

    /**
     * Render out the currently set contents.
     *
     * @param boolean $inline  Are we viewing inline?
     *
     * @return array  See self::render().
     */
    protected function _IMPrender($inline)
    {
        $can_display = !$inline;
        $text = '';

        $subparts = $this->_mimepart->contentTypeMap();
        $ids = array_keys($subparts);
        unset($subparts[key($subparts)]);

        /* Look at the 'start' parameter to determine which part to start
         * with. If no 'start' parameter, use the first part. RFC 2387
         * [3.1] */
        $id = $this->_mimepart->getContentTypeParameter('start');
        if (is_null($id)) {
            reset($subparts);
            $id = key($subparts);
        }

        /* Only display if the start part (normally text/html) can be
         * displayed inline -OR- we are viewing this part as an attachment. */
        if (!$can_display) {
            $viewer = Horde_Mime_Viewer::factory($subparts[$id]);
            if ($viewer->canRender('inline')) {
                $mime_part = $this->_mimepart->getPart($id);
                $can_display = ($mime_part->getDisposition() == 'inline');
            }
        }

        if ($can_display) {
            /* Build a list of parts -> CIDs. */
            $cids = array();
            foreach (array_keys($subparts) as $val) {
                $part = $this->_mimepart->getPart($val);
                $cids[$val] = $part->getContentId();
            }

            $ret = $this->_params['contents']->renderMIMEPart($id, $inline ? 'inline' : 'full', array('params' => array_merge($this->_params, array('related_id' => $id, 'related_cids' => $cids))));
            $ret['ids'] = array_keys(array_flip(array_merge($ret['ids'], $ids)));
            unset($ret['summary_id']);
            return $ret;
        }

        return array();
    }
}
