<?php

$block_name = _("Menu Alarms");
$block_type = 'tree';

/**
 * @package Horde_Block
 */
class Horde_Block_kronolith_tree_alarms extends Horde_Block
{
    protected $_app = 'kronolith';

    protected function _buildTree(&$tree, $indent = 0, $parent = null)
    {
        $horde_alarm = $GLOBALS['injector']->getInstance('Horde_Alarm');

        $alarmCount = 0;
        try {
            $alarms = Kronolith::listAlarms(new Horde_Date($_SERVER['REQUEST_TIME']),
                                            $GLOBALS['display_calendars'],
                                            true);
        } catch (Exception $e) {
            return;
        }
        foreach ($alarms as $calId => $calAlarms) {
            foreach ($calAlarms as $event) {
                if ($horde_alarm->isSnoozed($event->uid, $GLOBALS['registry']->getAuth())) {
                    continue;
                }
                $alarmCount++;
                $tree->addNode($parent . $calId . $event->id,
                               $parent,
                               htmlspecialchars($event->getTitle(), ENT_COMPAT, $GLOBALS['registry']->getCharset()),
                               $indent + 1,
                               false,
                               array('icon' => 'alarm.png',
                                     'icondir' => (string)Horde_Themes::img(),
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
                              'icon' => (string)$GLOBALS['registry']->get('icon', $parent),
                              'icondir' => '');
        $pnode_name = $GLOBALS['registry']->get('name', $parent);
        if ($alarmCount) {
            $pnode_name = '<strong>' . $pnode_name . '</strong>';
        }

        $tree->addNode($parent, $GLOBALS['registry']->get('menu_parent', $parent),
                       $pnode_name, $indent, false, $pnode_params);
    }

}
