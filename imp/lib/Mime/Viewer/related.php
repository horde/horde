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
        'info' => false,
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
        return $this->_IMPrender(true);
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
        $related_id = $this->_mimepart->getMimeId();

        /* Look at the 'start' parameter to determine which part to start
         * with. If no 'start' parameter, use the first part. RFC 2387
         * [3.1] */
        $id = $this->_mimepart->getContentTypeParameter('start');
        if (is_null($id)) {
            $id = Horde_Mime::mimeidArithmetic($related_id, 'down');
        }

        /* Only display if the start part (normally text/html) can be
         * displayed inline -OR- we are viewing this part as an attachment. */
        if ($inline &&
            !$this->_params['contents']->canDisplay($id, IMP_Contents::RENDER_INLINE)) {
            return array();
        }

        $cids = $ret = array();
        $ids = array_keys($this->_mimepart->contentTypeMap());

        /* Build a list of parts -> CIDs. */
        foreach ($ids as $val) {
            $ret[$val] = null;
            if (strcmp($related_id, $val) !== 0) {
                $part = $this->_mimepart->getPart($val);
                $cids[$val] = $part->getContentId();
            }
        }

        $render = $this->_params['contents']->renderMIMEPart($id, $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL, array('params' => array_merge($this->_params, array('related_id' => $id, 'related_cids' => $cids))));

        foreach (array_keys($render) as $val) {
            $ret[$val] = $render[$val];
        }

        return $ret;
    }
}
