<?php

$block_name = _("Menu Alarms");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_kronolith_tree_alarms extends Horde_Block {

    var $_app = 'kronolith';

    function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        require_once dirname(__FILE__) . '/../base.php';

        $horde_alarm = null;
        if (!empty($GLOBALS['conf']['alarms']['driver'])) {
            $horde_alarm = Horde_Alarm::factory();
        }

        $alarmCount = 0;
        $alarms = Kronolith::listAlarms(new Horde_Date($_SERVER['REQUEST_TIME']),
                                        $GLOBALS['display_calendars'],
                                        true);
        if (is_a($alarms, 'PEAR_Error')) {
            return $alarms;
        }
        foreach ($alarms as $calId => $calAlarms) {
            foreach ($calAlarms as $event) {
                if ($horde_alarm && $horde_alarm->isSnoozed($event->getUID(), Horde_Auth::getAuth())) {
                    continue;
                }
                $alarmCount++;
                $tree->addNode($parent . $calId . $event->getId(),
                               $parent,
                               $event->getTitle(),
                               $indent + 1,
                               false,
                               array('icon' => 'alarm.png',
                                     'icondir' => $GLOBALS['registry']->getImageDir(),
                                     'title' => $event->getTooltip(),
                                     'url' => $event->getViewUrl()));
            }
        }

        if ($GLOBALS['registry']->get('url', $parent)) {
            $purl = $GLOBALS['registry']->get('url', $parent);
        } elseif ($GLOBALS['registry']->get('status', $parent) == 'heading' ||
                  !$GLOBALS['registry']->get('webroot')) {
            $purl = null;
        } else {
            $purl = Horde::url($GLOBALS['registry']->getInitialPage($parent));
        }
        $pnode_params = array('url' => $purl,
                              'icon' => $GLOBALS['registry']->get('icon', $parent),
                              'icondir' => '');
        $pnode_name = $GLOBALS['registry']->get('name', $parent);
        if ($alarmCount) {
            $pnode_name = '<strong>' . $pnode_name . '</strong>';
        }

        $tree->addNode($parent, $GLOBALS['registry']->get('menu_parent', $parent),
                       $pnode_name, $indent, false, $pnode_params);
    }

}
