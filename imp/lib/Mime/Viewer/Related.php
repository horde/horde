<?php
/**
 * The IMP_Mime_Viewer_Related class handles multipart/related
 * (RFC 2387) messages.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Mime_Viewer_Related extends Horde_Mime_Viewer_Base
{
    /**
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     */
    protected $_metadata = array(
        'compressed' => false,
        'embedded' => false,
        'forceinline' => true
    );

    /**
     * The start MIME ID.
     *
     * @var string
     */
    protected $_start;

    /**
     */
    protected function _render()
    {
        return $this->_IMPrender(false);
    }

    /**
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

        if (!($id = $this->_init($inline))) {
            return array();
        }

        $render = $this->getConfigParam('imp_contents')->renderMIMEPart($id, $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL);

        if (!$inline) {
            foreach (array_keys($render) as $key) {
                if (!is_null($render[$key])) {
                    return array($related_id => $render[$key]);
                }
            }
            return null;
        }

        $data_id = null;
        $ret = array_fill_keys(array_keys($this->_mimepart->contentTypeMap()), null);

        foreach (array_keys($render) as $val) {
            $ret[$val] = $render[$val];
            if ($ret[$val]) {
                $data_id = $val;
            }
        }

        if (!is_null($data_id)) {
            $this->_mimepart->setMetadata('viewable_part', $data_id);

            /* We want the inline display to show multipart/related vs. the
             * viewable MIME part.  This is because a multipart/related part
             * is not downloadable and clicking on the MIME part may not
             * produce the desired result in the full display (i.e. HTML parts
             * with related images). */
            if ($data_id !== $related_id) {
                $ret[$related_id] = $ret[$data_id];
                $ret[$data_id] = null;
            }
        }

        /* Fix for broken messages that don't refer to a related CID within
         * the base part. */
        if ($unused = $this->_mimepart->getMetadata('related_cids_unused')) {
            foreach ($unused as $val) {
                $summary = $this->getConfigParam('imp_contents')->getSummary(
                    $val,
                    IMP_Contents::SUMMARY_SIZE |
                    IMP_Contents::SUMMARY_ICON |
                    IMP_Contents::SUMMARY_DESCRIP_LINK |
                    IMP_Contents::SUMMARY_DOWNLOAD
                );

                $ret[$related_id]['status'][] = array(
                    'icon' => Horde::img('alerts/error.png', _("Error")),
                    'text' => array(
                        _("This part contains an attachment that can not be displayed within this part:"),
                        implode('&nbsp;', array(
                            $summary['icon'],
                            $summary['description'],
                            $summary['size'],
                            $summary['download']
                        ))
                    )
                );
            }
        }

        return $ret;
    }

    /**
     * Initialization: determine start MIME ID.
     *
     * @param boolean $inline  Are we viewing inline?
     *
     * @return string  The start MIME ID, or null if the part is not viewable.
     */
    protected function _init($inline)
    {
        if (!isset($this->_start)) {
            $ids = array_keys($this->_mimepart->contentTypeMap());
            $related_id = $this->_mimepart->getMimeId();
            $cids = array();
            $id = null;

            /* Build a list of parts -> CIDs. */
            foreach ($ids as $val) {
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

            /* Set related information in message metadata. */
            $this->_mimepart->setMetadata('related_cids', $cids);

            $this->_start = $id;
        }

        /* Only display if the start part (normally text/html) can be
         * displayed inline -OR- we are viewing this part as an attachment. */
        return ($inline && !$this->getConfigParam('imp_contents')->canDisplay($this->_start, IMP_Contents::RENDER_INLINE))
            ? null
            : $this->_start;
    }

    /**
     */
    public function canRender($mode)
    {
        return (($mode == 'inline') && !$this->_init(true))
            ? false
            : parent::canRender($mode);
    }

}
