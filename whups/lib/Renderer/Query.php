<?php
/**
 * A Horde_Form_Renderer for rendering Whups queries.
 *
 * Copyright 2001-2002 Robert E. Coyle <robertecoyle@hotmail.com>
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @package Whups
 */

/*class Horde_Form_Renderer_QuerySetCurrentType extends Horde_Form_Renderer {

    function _renderForm(&$form, &$vars, $isActive)
    {
        global $whups_driver;

        $droptext = Horde_Core_Ui_VarRenderer_html::selectOptions($whups_driver->getAllTypes());
        include WHUPS_TEMPLATES . '/renderer/querysetcurrenttype.inc';
    }

    function submit()
    {
        // noop
    }
}*/

class Horde_Form_Renderer_Query extends Horde_Form_Renderer {

    var $ticketTypes = null;

    var $attributes = null;

    function Horde_Form_Renderer_Query()
    {
        $this->ticketTypes = $GLOBALS['whups_driver']->getAllTypes();
        $this->attributes = $GLOBALS['whups_driver']->getAllAttributes();
    }

    function _renderBegin()
    {
        echo '<table class="item">';
    }

    function _renderForm(&$query, &$vars, $active)
    {
        $this->currentRow = 1;
        $this->isActive = $active;
        $this->currentPath = $vars->get('path');

        $this->_renderBegin();
        $query->walk($this, '_renderRow');
        $this->_renderEnd();
    }

    function edit(&$operations, $formname, $id)
    {
        include WHUPS_TEMPLATES . '/renderer/query/edit.inc';
    }

    function _renderRow(&$more, &$path, $type, $criterion, $cvalue, $operator, $value)
    {
        global $whups_driver, $registry;

        $this->currentRow++;

        $pathstring = Whups_Query::pathToString($path);

        $depth = count($path);
        $class = "item" . ($this->currentRow % 2);

        switch ($type) {
        case QUERY_TYPE_AND: $text = _("And"); break;
        case QUERY_TYPE_OR:  $text = _("Or");  break;
        case QUERY_TYPE_NOT: $text = _("Not"); break;
        case QUERY_TYPE_CRITERION:

            switch ($criterion) {
            case CRITERION_ID:             $text = _("Id"); break;
            case CRITERION_OWNERS:         $text = _("Owners"); break;
            case CRITERION_GROUPS:         $text = _("Groups"); break;
            case CRITERION_REQUESTER:      $text = _("Requester"); break;
            case CRITERION_ADDED_COMMENT:  $text = _("Commentor"); break;
            case CRITERION_COMMENT:        $text = _("Comment"); break;
            case CRITERION_SUMMARY:        $text = _("Summary"); break;

            case CRITERION_QUEUE:
                $queue = $whups_driver->getQueue($value);
                if ($queue) {
                    $text = _("Queue");
                    $value = $queue['name'];
                }
                break;

            case CRITERION_VERSION:
                $version = $whups_driver->getVersion($value);
                if ($version) {
                    $text = _("Version");
                    $value = $version['name'];
                }
                break;

            case CRITERION_TYPE:
                $text = _("Type");
                $value = $whups_driver->getTypeName($value);
                break;

            case CRITERION_STATE:
                // The value of the following depends on the type.
                $state = $whups_driver->getState($value);
                if ($state && isset($this->ticketTypes[$state['type']])) {
                    $text = '[' . $this->ticketTypes[$state['type']] . '] ' .
                        _("State");
                    $value = $state['name'];
                }
                break;

            case CRITERION_PRIORITY:
                $state = $whups_driver->getPriority($value);
                $text = '[' . $this->ticketTypes[$state['type']] . '] ' .
                    _("Priority");
                $value = $state['name'];
                break;

            case CRITERION_ATTRIBUTE:
                // The value of the following depends on the type.
                $aname = $whups_driver->getAttributeName($cvalue);
                $type = $this->attributes[$cvalue]['type_id'];
                $text = '';
                if (isset($this->ticketTypes[$type])) {
                    $text = '[' . $this->ticketTypes[$type] . '] ';
                }
                $text .= sprintf("Attribute \"%s\"", $aname);
                break;

            case CRITERION_TIMESTAMP:
                $text = _("Created");
                $value = strftime($GLOBALS['prefs']->getValue('report_time_format'), $value);
                break;

            case CRITERION_UPDATED:
                $text = _("Updated");
                $value = strftime($GLOBALS['prefs']->getValue('report_time_format'), $value);
                break;

            case CRITERION_RESOLVED:
                $text = _("Resolved");
                $value = strftime($GLOBALS['prefs']->getValue('report_time_format'), $value);
                break;

            case CRITERION_ASSIGNED:
                $text = _("Assigned");
                $value = strftime($GLOBALS['prefs']->getValue('report_time_format'), $value);
                break;

            case CRITERION_DUE:
                $text = _("Due");
                $value = strftime($GLOBALS['prefs']->getValue('report_time_format'), $value);
                break;
            }

            if (!isset($text)) {
                $text = sprintf(_("Unknown node type %s"), $type);
                break;
            }

            $text .= ' ';

            switch ($operator) {
            case OPERATOR_GREATER:
                $text .= _("is greater than");
                break;

            case OPERATOR_LESS:
                $text .= _("is less than");
                break;

            case OPERATOR_EQUAL:
                $text .= _("is");
                break;

            case OPERATOR_CI_SUBSTRING:
                $text .= _("contains (case insensitive) substring");
                break;

            case OPERATOR_CS_SUBSTRING:
                $text .= _("contains (case sensitive) substring");
                break;

            case OPERATOR_WORD:
                $text .= _("contains the word");
                break;

            case OPERATOR_PATTERN:
                $text .= _("matches the pattern");
                break;
            }

            $text .= " $value";
            break;

        default:
            $text = sprintf(_("Unknown node type %s"), $type);
            break;
        }

        // Stick vertical-align: middle; on everything to make it look a
        // little nicer.
        $fimgattrs = 'height="20" width="0" style="vertical-align: middle;"';
        $imgattrs = 'height="20" width="20" style="vertical-align: middle;"';

        $space = '';
        $count = count($more);

        if ($count == 0) {
            // Always have at least one image to make sure all rows are the
            // same height.
            $space = Horde::img('tree/blank.png', '', $fimgattrs) . "\n";
        } else {
            for ($i = 0; $i < $count - 1; $i++) {
                if ($more[$i] == 1) {
                    $space .= Horde::img('tree/line.png', '|', $imgattrs) . "\n";
                } else {
                    $space .= Horde::img('tree/blank.png', '', $imgattrs) . "\n";
                }
            }
        }

        if ($count > 0) {
            if ($more[$count - 1] == 1) {
                $space .= Horde::img('tree/join.png', '+', $imgattrs) . "\n";
            } else {
                $space .= Horde::img('tree/joinbottom.png', '-', $imgattrs) . "\n";
            }
        }

        $extra  = ($this->isActive ? '' : ' disabled="disabled"');
        $extra .= ($pathstring == $this->currentPath ? ' checked="checked"' : '');
        include WHUPS_TEMPLATES . '/renderer/query/render.inc';
    }

    function _renderEnd()
    {
        echo '</table>';
    }

}
