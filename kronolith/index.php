<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * not receive such a file, see also http://www.fsf.org/copyleft/gpl.html.
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('kronolith');

/* Load traditional interface? */
if (!Kronolith::showAjaxView()) {
    if ($prefs->getValue('dynamic_view')) {
        $notification->push(_("Your browser is too old to display the dynamic mode. Using traditional mode instead."), 'horde.warning');
    }
    include KRONOLITH_BASE . '/' . $prefs->getValue('defaultview') . '.php';
    exit;
}

/* Load Ajax interface. */
$menu = new Horde_Menu();
$help_link = Horde::getServiceLink('help', 'kronolith');
if ($help_link) {
    $help_link = Horde::widget($help_link, _("Help"), 'helplink', 'help', Horde::popupJs($help_link, array('urlencode' => true)) . 'return false;');
}
$today = new Horde_Date($_SERVER['REQUEST_TIME']);

/* Suppress menus in prefs screen and indicate that notifications should use
 * the ajax mode. */
$session->set('horde', 'notification_override',
              array(KRONOLITH_BASE . '/lib/Notification/Listener/AjaxStatus.php',
                    'Kronolith_Notification_Listener_AjaxStatus'));

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

$injector->getInstance('Horde_Core_Factory_Imple')->create(
    array('kronolith', 'TagAutoCompleter'),
    array(
        'box' => 'kronolithEventACBox',
        'pretty' => true,
        'triggerId' => 'kronolithEventTags',
        'var' => 'KronolithCore.eventTagAc'
    )
);

$injector->getInstance('Horde_Core_Factory_Imple')->create(
    array('kronolith', 'TagAutoCompleter'),
    array(
        'box' => 'kronolithCalendarinternalACBox',
        'pretty' => true,
        'triggerContainer' => 'kronolithACCalendarTriggerContainer',
        'triggerId' => 'kronolithCalendarinternalTags',
        'var' => 'KronolithCore.calendarTagAc'
    )
);

$injector->getInstance('Horde_Core_Factory_Imple')->create(
    array('kronolith', 'ContactAutoCompleter'),
    array(
        'box' => 'kronolithAttendeesACBox',
        'onAdd' => 'KronolithCore.addAttendee.bind(KronolithCore)',
        'onRemove' => 'KronolithCore.removeAttendee.bind(KronolithCore)',
        'pretty' => true,
        'triggerContainer' => 'kronolithAttendeesACTriggerContainer',
        'triggerId' => 'kronolithEventAttendees',
        'var' => 'KronolithCore.attendeesAc'
    )
);

if ($conf['maps']['driver']) {
    Kronolith::initEventMap($conf['maps']);
}

Kronolith::header();

echo "<body class=\"kronolithAjax\">\n";

require KRONOLITH_TEMPLATES . '/index/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();

echo "</body>\n</html>";
