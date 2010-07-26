<?php
/**
 * The IMP_Horde_Mime_Viewer_Alternative class renders out messages from
 * multipart/alternative content types (RFC 2046 [5.1.4]).
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
class IMP_Horde_Mime_Viewer_Alternative extends Horde_Mime_Viewer_Base
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
        'forceinline' => false
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
     * @param boolean $inline        Are we viewing inline?
     * @param boolean $prefer_plain  For MIMP, prefer text/plain part over
     *                               all others.
     *
     * @return array  See parent::render().
     */
    protected function _IMPrender($inline, $prefer_plain = true)
    {
        $base_id = $this->_mimepart->getMimeId();
        $subparts = $this->_mimepart->contentTypeMap();

        $base_ids = $display_ids = $ret = array();

        $is_mimp = (IMP::getViewMode() == 'mimp');

        /* Look for a displayable part. RFC: show the LAST choice that can be
         * displayed inline. If an alternative is itself a multipart, the user
         * agent is allowed to show that alternative, an earlier alternative,
         * or both. If we find a multipart alternative that contains at least
         * one viewable part, we will display all viewable subparts of that
         * alternative. */
        foreach ($subparts as $mime_id => $type) {
            $ret[$mime_id] = null;
            if ((strcmp($base_id, $mime_id) !== 0) &&
                $this->_params['contents']->canDisplay($mime_id, $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL)) {
                if (!$is_mimp ||
                    !$prefer_plain ||
                    ($type == 'text/plain')) {
                    $display_ids[strval($mime_id)] = true;
                }
            }
        }

        /* If we found no IDs, return now. */
        if (empty($display_ids)) {
            if ($is_mimp && $prefer_plain) {
                return $this->_IMPRender($inline, false);
            }
            $ret[$base_id] = array(
                'data' => '',
                'status' => array(
                    array(
                        'text' => array(_("There are no alternative parts that can be displayed inline.")),
                        'type' => 'info'
                    )
                ),
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            );
            return $ret;
        }

        /* If the last viewable message exists in a subpart, back up to the
         * base multipart and display all viewable parts in that multipart.
         * Else, display the single part. */
        end($display_ids);
        $curr_id = key($display_ids);
        while (!is_null($curr_id) && (strcmp($base_id, $curr_id) !== 0)) {
            if (isset($subparts[$curr_id])) {
                $disp_id = $curr_id;
            }
            $curr_id = Horde_Mime::mimeIdArithmetic($curr_id, 'up');
        }

        /* Now grab all keys under this ID. */
        $render_part = $this->_mimepart->getPart($disp_id);
        foreach (array_keys($render_part->contentTypeMap()) as $val) {
            if (isset($display_ids[$val])) {
                $render = $this->_params['contents']->renderMIMEPart($val, $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL, array('params' => $this->_params));
                foreach (array_keys($render) as $id) {
                    unset($display_ids[$id]);
                    if (!$inline) {
                        if (!is_null($render[$id])) {
                            return array($base_id => $render[$id]);
                        }
                    } else {
                        $ret[$id] = $render[$id];
                    }
                }
            } elseif (($disp_id != $val) && !array_key_exists($val, $ret)) {
                // Need array_key_exists() here since we are checking if the
                // key exists AND is null.
                $ret[$val] = array(
                    'attach' => true,
                    'data' => '',
                    'status' => array(),
                    'type' => 'application/octet-stream'
                );
            }
        }

        return $inline
            ? $ret
            : null;
    }
}
