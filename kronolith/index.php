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
if (!$prefs->getValue('dynamic_view') || !$browser->hasFeature('xmlhttpreq') ||
    ($browser->isBrowser('msie') && $browser->getMajor() < 7) ||
    ($browser->hasFeature('issafari') && $browser->getMajor() < 2)) {
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

/* Suppress menus in options screen and indicate that notifications should use
 * the ajax mode. */
Horde_Core_Prefs_Ui::hideMenu(true);
$_SESSION['horde_notification']['override'] = array(
    KRONOLITH_BASE . '/lib/Notification/Listener/AjaxStatus.php',
    'Kronolith_Notification_Listener_AjaxStatus'
);

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

Horde_Ajax_Imple::factory(
    array('kronolith', 'TagAutoCompleter'),
    array('triggerId' => 'kronolithEventTags',
          'box' => 'kronolithEventACBox',
          'pretty' => true,
          'var' => 'KronolithCore.eventTagAc'))
    ->attach();

Horde_Ajax_Imple::factory(
    array('kronolith', 'TagAutoCompleter'),
    array('triggerId' => 'kronolithCalendarinternalTags',
          'triggerContainer' => 'kronolithACCalendarTriggerContainer',
          'box' => 'kronolithCalendarinternalACBox',
          'pretty' => true,
          'var' => 'KronolithCore.calendarTagAc'))
    ->attach();

Horde_Ajax_Imple::factory(
    array('kronolith', 'ContactAutoCompleter'),
    array('triggerId' => 'kronolithEventAttendees',
          'triggerContainer' => 'kronolithAttendeesACTriggerContainer',
          'box' => 'kronolithAttendeesACBox',
          'pretty' => true,
          'var' => 'KronolithCore.attendeesAc'))
    ->attach();

Kronolith::header();
echo "<body class=\"kronolithAjax\">\n";
require KRONOLITH_TEMPLATES . '/index/index.inc';
Horde::includeScriptFiles();
Horde::outputInlineScript();

/* Maps must be initialized after scripts are output, to avoid having them
 * included in the monolithic javascript file, which breaks loading the hordemap
 * dependencies. */
if ($conf['maps']['driver']) {
    Kronolith::initEventMap($conf['maps']);
}

echo "</body>\n</html>";
