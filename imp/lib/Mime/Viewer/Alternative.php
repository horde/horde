<?php
/**
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Renderer for multipart/alternative content (RFC 2046 [5.1.4]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Viewer_Alternative extends Horde_Mime_Viewer_Base
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
     * @return array  See parent::render().
     */
    protected function _IMPrender($inline)
    {
        $base_id = $this->_mimepart->getMimeId();
        $display_ids = $ret = array();
        $prefer_plain = ($GLOBALS['prefs']->getValue('alternative_display') == 'text');

        /* Look for a displayable part. RFC: show the LAST choice that can be
         * displayed inline. If an alternative is itself a multipart, the user
         * agent is allowed to show that alternative, an earlier alternative,
         * or both. If we find a multipart alternative that contains at least
         * one viewable part, we will display all viewable subparts of that
         * alternative. */
        $imp_contents = $this->getConfigParam('imp_contents');
        foreach ($this->_mimepart->partIterator() as $val) {
            $id = $val->getMimeId();
            $ret[$id] = null;

            if ((strcmp($base_id, $id) !== 0) &&
                $imp_contents->canDisplay($id, $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL) &&
                /* Show HTML if $prefer_plain is false-y or if
                 * alternative_display is not 'html'. */
                (!$prefer_plain ||
                 (($val->getType() != 'text/html') &&
                  ($val->getPrimaryType() != 'text')))) {
                $display_ids[strval($id)] = true;
            }
        }

        /* If we found no IDs, return now. */
        if (empty($display_ids)) {
            $ret[$base_id] = array(
                'data' => '',
                'status' => new IMP_Mime_Status(
                    $this->_mimepart,
                    _("There are no alternative parts that can be displayed inline.")
                ),
                'type' => 'text/html; charset=' . $this->getConfigParam('charset')
            );
            return $ret;
        }

        /* If the last viewable message exists in a subpart, back up to the
         * base multipart and display all viewable parts in that multipart.
         * Else, display the single part. */
        end($display_ids);
        $curr_id = key($display_ids);
        while (!is_null($curr_id) && (strcmp($base_id, $curr_id) !== 0)) {
            if (array_key_exists($curr_id, $ret)) {
                $disp_id = $curr_id;
            }

            $id_ob = new Horde_Mime_Id($curr_id);
            $curr_id = $id_ob->idArithmetic($id_ob::ID_UP);
        }

        /* At this point, $ret contains stubs for all parts living in the base
         * alternative part.
         * Go through all subparts of displayable part and make sure all parts
         * are rendered.  Parts not rendered will be marked as not being
         * handled by this viewer (Bug #9365). */
        $render_part = $this->_mimepart[$disp_id];
        foreach ($render_part->partIterator() as $val) {
            $id = $val->getMimeId();
            $need_render[$id] = $subparts[$id] = true;
        }

        /* Track whether there is at least one viewable (non-empty) part. */
        $viewable = false;
        $viewable_ret = $ret;

        foreach (array_keys($subparts) as $val) {
            if (isset($display_ids[$val]) && isset($need_render[$val])) {
                $render = $this->getConfigParam('imp_contents')->renderMIMEPart(
                    $val,
                    $inline ? IMP_Contents::RENDER_INLINE : IMP_Contents::RENDER_FULL
                );

                foreach (array_keys($render) as $id) {
                    unset($need_render[$id]);

                    if (!$inline) {
                        if (!is_null($render[$id])) {
                            return array($base_id => $render[$id]);
                        }
                    } else {
                        $ret[$id] = $render[$id];
                        if (!is_null($ret[$id])) {
                            $viewable = true;
                        }
                    }
                }
            }
        }

        if (!$inline) {
            return null;
        }

        unset($need_render[$disp_id]);
        foreach (array_keys($need_render) as $val) {
            unset($ret[$val]);
        }

        /* If we reach this point, and have at least one subpart with no
         * viewable parts, check to see there is not a richer, non-inline
         * viewable part that exists in the message. */
        if (!$viewable) {
            $id_ob = new Horde_Mime_Id($disp_id);
            if (array_key_exists($id_ob->idArithmetic($id_ob::ID_NEXT), $viewable_ret)) {
                $ret[$disp_id] = array(
                    'data' => '',
                    'status' => new IMP_Mime_Status(
                        $this->_mimepart,
                        array(
                            _("This part contains no message contents."),
                            /* TODO: list other alternative parts? */
                            _("There are no alternative parts that can be displayed inline.")
                        )
                    ),
                    'type' => 'text/html; charset=' . $this->getConfigParam('charset')
                );
            }
        }

        return $ret;
    }

}
