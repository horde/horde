<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Wraps all display issues for a message part in a status message that hides
 * the details by default.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Mime_Status_RenderIssue_Display extends IMP_Mime_Status
{
    /**
     * Render issues.
     *
     * @var array
     */
    protected $_issues = array();

    /**
     * Add render issues to queue.
     *
     * @param array $issues  Render issues.
     */
    public function addIssues(array $issues)
    {
        $this->_issues = array_merge($this->_issues, $issues);
    }

    /**
     * Output status block HTML.
     *
     * @return string  The formatted status message HTML.
     */
    public function __toString()
    {
        global $registry;

        $out = '';

        switch ($registry->getView()) {
        case $registry::VIEW_SMARTMOBILE:
            break;

        default:
            $unique_id = strval(new Horde_Support_Randomid());

            $this->icon('info_icon.png', _("Info"));
            $this->_text = array(
                Horde::link('#', '', 'showRenderIssues', '', '', '', '', array(
                    'domid' => $unique_id
                )) . _("Click to display message part errors.") . '</a>'
            );

            $out = parent::__toString();

            $out .= '<div id="' . $unique_id . '" style="display:none">';
            foreach ($this->_issues as $val) {
                $out .= strval($val);
            }
            $out .= '</div>';
        }

        return $out;
    }

}
