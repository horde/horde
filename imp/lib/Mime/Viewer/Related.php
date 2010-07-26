<?php
/**
 * The IMP_Horde_Mime_Viewer_Related class handles multipart/related
 * (RFC 2387) messages.
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
class IMP_Horde_Mime_Viewer_Related extends Horde_Mime_Viewer_Base
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * Metadata for the current viewer/data.
     *
     * @var array
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => false,
        'forceinline' => true
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        return $this->_IMPrender(false);
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
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
        $ids = array_keys($this->_mimepart->contentTypeMap());
        $related_id = $this->_mimepart->getMimeId();

        $cids = $ret = array();
        $id = null;

        /* Build a list of parts -> CIDs. */
        foreach ($ids as $val) {
            $ret[$val] = null;
            if (strcmp($related_id, $val) !== 0) {
                $part = $this->_mimepart->getPart($val);
                $cids[$val] = $part->getContentId();
            }
        }

        /* Look at the 'start' parameter to determine which part to start
         * with. If no 'start' parameter, use the first part. RFC 2387
         * [3.1] */
        $start = $this->_mimepart->getContentTypeParameter('start');
        if (!empty($start)) {
            $id = array_search($id, $cids);
        }

        if (empty($id)) {
            reset($ids);
            $id = next($ids);
        }

        /* Only display if the start part (normally text/html) can be
         * displayed inline -OR- we are viewing this part as an attachment. */
        if ($inline &&
            !$this->_params['contents']->canDisplay($id, IMP_Contents::RENDER_INLINE)) {
            return array();
        }

        $render = $this->_params['contents']->renderMIMEPart($id, $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL, array('params' => array_merge($this->_params, array('related_id' => $related_id, 'related_cids' => $cids))));

        if (!$inline) {
            foreach (array_keys($render) as $key) {
                if (!is_null($render[$key])) {
                    return array($related_id => $render[$key]);
                }
            }
            return null;
        }

        $data_id = null;
        foreach (array_keys($render) as $val) {
            $ret[$val] = $render[$val];
            if ($ret[$val]) {
                $data_id = $val;
            }
        }

        /* We want the inline display to show multipart/related vs. the
         * viewable MIME part.  This is because a multipart/related part is
         * not downloadable and clicking on the MIME part may not produce the
         * desired result in the full display (i.e. HTML parts with related
         * images). */
        if (!is_null($data_id) && ($data_id !== $related_id)) {
            $ret[$related_id] = $ret[$data_id];
            $ret[$data_id] = null;
        }

        return $ret;
    }

}
