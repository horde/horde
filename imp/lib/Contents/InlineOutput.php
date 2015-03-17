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
 * Render message data meant for inline viewing in a browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2002-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contents_InlineOutput
{
    /**
     * Generate inline message display.
     *
     * @param IMP_Contents $contents  Contents object.
     * @param array $options          Options:
     *   - mask: (integer) The mask needed for a getSummary() call.
     *   - mimeid: (string) Restrict output to this MIME ID (and children).
     *   - part_info_display: (array) The list of summary fields to display.
     *
     * @return array  An array with the following keys:
     *   - atc_parts: (array) The list of attachment MIME IDs.
     *   - display_ids: (array) The list of display MIME IDs.
     *   - metadata: (array) A list of metadata.
     *   - msgtext: (string) The rendered HTML code.
     *   - one_part: (boolean) If true, the message only consists of one part.
     */
    public function getInlineOutput(IMP_Contents $contents,
                                    array $options = array())
    {
        global $prefs, $registry;

        $atc_parts = $display_ids = $i = $metadata = $msgtext = $wrap_ids = array();
        $text_out = '';
        $view = $registry->getView();

        $contents_mask = isset($options['mask'])
            ? $options['mask']
            : 0;
        $mimeid_filter = isset($options['mimeid'])
            ? new Horde_Mime_Id($options['mimeid'])
            : null;
        $part_info_display = isset($options['part_info_display'])
            ? $options['part_info_display']
            : array();
        $show_parts = $prefs->getValue('parts_display');

        foreach ($contents->getMIMEMessage()->partIterator() as $part) {
            $mime_id = $part->getMimeId();
            $i[] = $mime_id;

            if (isset($display_ids[$mime_id]) ||
                isset($atc_parts[$mime_id])) {
                continue;
            }

            if ($mimeid_filter &&
                ((strval($mimeid_filter) != $mime_id) &&
                 !$mimeid_filter->isChild($mime_id))) {
                 continue;
            }

            if (!($render_mode = $contents->canDisplay($mime_id, IMP_Contents::RENDER_INLINE_AUTO))) {
                if (IMP_Mime_Attachment::isAttachment($part)) {
                    if ($show_parts == 'atc') {
                        $atc_parts[$mime_id] = 1;
                    }

                    if ($contents_mask) {
                        $msgtext[$mime_id] = array(
                            'text' => $this->_formatSummary($contents, $mime_id, $contents_mask, $part_info_display, true)
                        );
                    }
                }
                continue;
            }

            $render_part = $contents->renderMIMEPart($mime_id, $render_mode);
            if (($show_parts == 'atc') &&
                IMP_Mime_Attachment::isAttachment($part) &&
                (empty($render_part) ||
                 !($render_mode & $contents::RENDER_INLINE))) {
                $atc_parts[$mime_id] = 1;
            }

            if (empty($render_part)) {
                if ($contents_mask &&
                    IMP_Mime_Attachment::isAttachment($part)) {
                    $msgtext[$mime_id] = array(
                        'text' => $this->_formatSummary($contents, $mime_id, $contents_mask, $part_info_display, true)
                    );
                }
                continue;
            }

            reset($render_part);
            while (list($id, $info) = each($render_part)) {
                $display_ids[$id] = 1;

                if (empty($info)) {
                    continue;
                }

                $part_text = ($contents_mask && empty($info['nosummary']))
                    ? $this->_formatSummary($contents, $id, $contents_mask, $part_info_display, !empty($info['attach']))
                    : '';

                if (empty($info['attach'])) {
                    if (isset($info['status'])) {
                        if (!is_array($info['status'])) {
                            $info['status'] = array($info['status']);
                        }

                        $render_issues = array();

                        foreach ($info['status'] as $val) {
                            if (in_array($view, $val->views)) {
                                if ($val instanceof IMP_Mime_Status_RenderIssue) {
                                    $render_issues[] = $val;
                                } else {
                                    $part_text .= strval($val);
                                }
                            }
                        }

                        if (!empty($render_issues)) {
                            $render_issues_ob = new IMP_Mime_Status_RenderIssue_Display();
                            $render_issues_ob->addIssues($render_issues);
                            $part_text .= strval($render_issues_ob);
                        }
                    }

                    $part_text .= '<div class="mimePartData">' . $info['data'] . '</div>';
                } elseif ($show_parts == 'atc') {
                    $atc_parts[$id] = 1;
                }

                $msgtext[$id] = array(
                    'text' => $part_text,
                    'wrap' => empty($info['wrap']) ? null : $info['wrap']
                );

                if (isset($info['metadata'])) {
                    /* Format: array(identifier, ...[data]...) */
                    $metadata = array_merge($metadata, $info['metadata']);
                }
            }
        }

        if (!empty($msgtext)) {
            uksort($msgtext, 'strnatcmp');
        }

        reset($msgtext);
        while (list($id, $part) = each($msgtext)) {
            while (!empty($wrap_ids)) {
                $id_ob = new Horde_Mime_Id(end($wrap_ids));
                if ($id_ob->isChild($id)) {
                    break;
                }
                array_pop($wrap_ids);
                $text_out .= '</div>';
            }

            if (!empty($part['wrap'])) {
                $text_out .= '<div class="' . $part['wrap'] .
                    '" impcontentsmimeid="' . $id . '">';
                $wrap_ids[] = $id;
            }

            $text_out .= '<div class="mimePartBase"' .
                (empty($part['wrap']) ? ' impcontentsmimeid="' . $id .  '"' : '') .
                '>' . $part['text'] . '</div>';
        }

        $text_out .= str_repeat('</div>', count($wrap_ids));

        if (!strlen($text_out)) {
            $text_out = strval(new IMP_Mime_Status(
                null,
                _("There are no parts that can be shown inline.")
            ));
        }

        $atc_parts = ($show_parts == 'all')
            ? $i
            : array_keys($atc_parts);

        return array(
            'atc_parts' => $atc_parts,
            'display_ids' => array_keys($display_ids),
            'metadata' => $metadata,
            'msgtext' => $text_out,
            'one_part' => (count($i) === 1)
        );
    }

    /**
     * Prints out a MIME summary (in HTML).
     *
     * @param IMP_Contents $contents  Contents object.
     * @param string $id              The MIME ID.
     * @param integer $mask           A bitmask indicating what summary
     *                                information to return.
     * @param array $display          The fields to display (in this order).
     * @param boolean $atc            Is this an attachment?
     *
     * @return string  The formatted summary string.
     */
    protected function _formatSummary(IMP_Contents $contents, $id, $mask,
                                      $display, $atc = false)
    {
        $summary = $contents->getSummary($id, $mask);
        $tmp_summary = array();

        foreach ($display as $val) {
            if (isset($summary[$val])) {
                switch ($val) {
                case 'description':
                    $summary[$val] = '<span class="mimePartInfoDescrip">' . $summary[$val] . '</span>';
                    break;

                case 'size':
                    $summary[$val] = '<span class="mimePartInfoSize">(' . $summary[$val] . ')</span>';
                    break;
                }
                $tmp_summary[] = $summary[$val];
            }
        }

        return '<div class="mimePartInfo' .
            ($atc ? ' mimePartInfoAtc' : '') .
            '"><div>' .
            implode(' ', $tmp_summary) .
            '</div></div>';
    }

}
