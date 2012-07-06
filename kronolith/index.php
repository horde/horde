<?php
/**
 * Copyright 1999-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.horde.org/licenses/gpl.
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('kronolith', array('nodynamicinit' => true));

/* Determine View */
switch ($registry->getView()) {
case Horde_Registry::VIEW_MINIMAL:
case Horde_Registry::VIEW_SMARTMOBILE:
    include KRONOLITH_BASE . '/smartmobile.php';
    exit;

case Horde_Registry::VIEW_BASIC:
case Horde_Registry::VIEW_DYNAMIC:
    if ($registry->getView() == Horde_Registry::VIEW_DYNAMIC &&
        $prefs->getValue('dynamic_view')) {
        break;
    }
    include KRONOLITH_BASE . '/' . $prefs->getValue('defaultview') . '.php';
    exit;
}

/* Load Ajax interface. */
$menu = new Horde_Menu();
$help_link = $registry->getServiceLink('help', 'kronolith');
if ($help_link) {
    $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
}
$today = new Horde_Date($_SERVER['REQUEST_TIME']);
$ampm = !$prefs->getValue('twentyFour');

$eventAlarmMethods = $eventAlarmParams = $taskAlarmMethods = $taskAlarmParams = '';
foreach ($injector->getInstance('Horde_Alarm')->handlers() as $method => $handler) {
    $eventAlarmMethods .= ' <input type="checkbox" name="event_alarms[]" id="kronolithEventAlarm' . $method . '" value="' . $method . '" /> <label for="kronolithEventAlarm' . $method . '">' . $handler->getDescription() . '</label>';
    $taskAlarmMethods .= ' <input type="checkbox" name="task[alarm_methods][]" id="kronolithTaskAlarm' . $method . '" value="' . $method . '" /> <label for="kronolithTaskAlarm' . $method . '">' . $handler->getDescription() . '</label>';
    $params = $handler->getParameters();
    if (!count($params)) {
        continue;
    }
    $eventAlarmParams .= ' <div id="kronolithEventAlarm' . $method . 'Params" style="display:none">';
    $taskAlarmParams .= ' <div id="kronolithTaskAlarm' . $method . 'Params" style="display:none">';
    foreach ($params as $name => $param) {
        $eventAlarmParams .= ' <label for="kronolithEventAlarmParam' . $name
            . '">' . $param['desc'] . '</label> ';
        $eventNameAtt = 'name="event_alarms_' . $name . '"';
        $eventAtt = 'id="kronolithEventAlarmParam' . $name . '" ' . $eventNameAtt;
        $taskAlarmParams .= ' <label for="kronolithTaskAlarmParam' . $name
            . '">' . $param['desc'] . '</label> ';
        $taskNameAtt = 'name="task[methods][' . $method . '][' . $name . ']"';
        $taskAtt = 'id="kronolithTaskAlarmParam' . $name . '" ' . $taskNameAtt;
        switch ($param['type']) {
        case 'text':
            $eventAlarmParams .= '<input type="text" ' . $eventAtt . ' />';
            $taskAlarmParams .= '<input type="text" ' . $taskAtt . ' />';
            break;
        case 'boolean':
            $eventAlarmParams .= '<input type="checkbox" ' . $eventAtt . ' />';
            $taskAlarmParams .= '<input type="checkbox" ' . $taskAtt . ' />';
            break;
        case 'sound':
            $eventAlarmParams .= '<ul class="sound-list"><li><input type="radio" ' . $eventAtt
                . ' value="" checked="checked" /> ' . _("No Sound") . '</li>';
            $taskAlarmParams .= '<ul class="sound-list"><li><input type="radio" ' . $taskAtt
                . ' value="" checked="checked" /> ' . _("No Sound") . '</li>';
            foreach (Horde_Themes::soundList() as $key => $val) {
                $sound = htmlspecialchars($key);
                $value = sprintf('<li><input type="radio" id="%s%s" %s value="%s" /> <embed autostart="false" src="%s" /> %s</li>',
                                 '%s',
                                 $name . str_replace('.wav', '', $sound),
                                 '%s',
                                 $sound,
                                 htmlspecialchars($val->uri),
                                 $sound);
                $eventAlarmParams .= sprintf($value,
                                             'kronolithEventAlarmParam',
                                             $eventNameAtt);
                $taskAlarmParams .= sprintf($value,
                                             'kronolithTaskAlarmParam',
                                             $taskNameAtt);
            }
            $eventAlarmParams .= '</ul>';
            $taskAlarmParams .= '</ul>';
            break;
        }
        $eventAlarmParams .= '<br />';
        $taskAlarmParams .= '<br />';
    }
    $eventAlarmParams = substr($eventAlarmParams, 0, - 6) . '</div>';
    $taskAlarmParams = substr($taskAlarmParams, 0, - 6) . '</div>';
}

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_TagAutoCompleter', array(
    'box' => 'kronolithEventACBox',
    'id' => 'kronolithEventTags',
    'pretty' => true
));

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_TagAutoCompleter', array(
    'box' => 'kronolithCalendarinternalACBox',
    'id' => 'kronolithCalendarinternalTags',
    'pretty' => true,
    'triggerContainer' => 'kronolithACCalendarTriggerContainer'
));

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_ContactAutoCompleter', array(
    'box' => 'kronolithAttendeesACBox',
    'id' => 'kronolithEventAttendees',
    'onAdd' => 'function(attendee) { KronolithCore.addAttendee(attendee); KronolithCore.checkOrganizerAsAttendee(); }',
    'onRemove' => 'KronolithCore.removeAttendee.bind(KronolithCore)',
    'pretty' => true,
    'triggerContainer' => 'kronolithAttendeesACTriggerContainer'
));

$injector->getInstance('Horde_Core_Factory_Imple')->create('Kronolith_Ajax_Imple_ResourceAutoCompleter', array(
    'box' => 'kronolithResourceACBox',
    'id' => 'kronolithEventResources',
    'onAdd' => 'KronolithCore.addResource.bind(KronolithCore)',
    'onRemove' => 'KronolithCore.removeResource.bind(KronolithCore)',
    'pretty' => true,
    'triggerContainer' => 'kronolithResourceACTriggerContainer'
));

if ($conf['maps']['driver']) {
    Kronolith::initEventMap($conf['maps']);
}

$injector->getInstance('Kronolith_Ajax')->init();

require KRONOLITH_TEMPLATES . '/index/index.inc';

$page_output->includeScriptFiles();
$page_output->outputInlineScript();

echo "</body>\n</html>";
