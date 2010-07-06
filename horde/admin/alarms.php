<?php
/**
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Jan Schneider <jan@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

$horde_alarm = $injector->getInstance('Horde_Alarm');
$methods = array();
foreach ($horde_alarm->handlers() as $name => $method) {
    $methods[$name] = $method->getDescription();
}

$vars = Horde_Variables::getDefaultVariables();

$form = new Horde_Form($vars, _("Add new alarm"));
$form->addHidden('', 'alarm', 'text', false);
$form->addVariable(_("Alarm title"), 'title', 'text', true);
$form->addVariable(_("Alarm start"), 'start', 'datetime', true);
$form->addVariable(_("Alarm end"), 'end', 'datetime', false);
$form->addVariable(_("Alarm text"), 'text', 'longtext', false);
$form->addVariable(_("Alarm methods"), 'methods', 'multienum', true, false, null, array($methods, min(5, count($methods))));
foreach ($horde_alarm->handlers() as $name => $method) {
    $params = $method->getParameters();
    if (!count($params)) {
        continue;
    }
    $form->addVariable($method->getDescription(), '', 'header', false);
    foreach ($params as $param => $param_info) {
        $form->addVariable($param_info['desc'], $name . '_' . $param, $param_info['type'], false);
    }
}

if ($form->validate()) {
    $form->getInfo($vars, $info);
    if (empty($info['alarm'])) {
        $info['alarm'] = date('YmdHis') . '.'
            . substr(str_pad(base_convert(microtime(), 10, 36), 16, uniqid(mt_rand()), STR_PAD_LEFT), -16)
            . '@' . $GLOBALS['conf']['server']['name'];
    }

    $params = array();
    foreach ($info['methods'] as $method) {
        foreach ($info as $name => $value) {
            if (strpos($name, $method . '_') === 0) {
                $params[$method][substr($name, strlen($method) + 1)] = $value;
            }
        }
    }

    // Full path to any sound files.
    if (!empty($params['notify']['sound'])) {
        $params['notify']['sound'] = $registry->get('themesuri', 'horde') . '/sounds/' . $params['notify']['sound'];
    }

    try {
        $horde_alarm->set(array(
            'id' => $info['alarm'],
            'title' => $info['title'],
            'text' => $info['text'],
            'start' => new Horde_Date($info['start']),
            'end' => empty($info['end']) ? null : new Horde_Date($info['end']),
            'methods' => $info['methods'],
            'params' => $params
        ));
        $notification->push(_("The alarm has been saved."), 'horde.success');
    } catch (Horde_Alarm_Exception $e) {
        $notification->push($e);
    }
}

$id = $vars->get('alarm');
if ($id) {
    if ($vars->get('delete')) {
        try {
            $horde_alarm->delete($id, '');
            $notification->push(_("The alarm has been deleted."), 'horde.success');
        } catch (Horde_Alarm_Exception $e) {
            $notification->push($e);
            $id = null;
        }
    } else {
        try {
            $alarm = $horde_alarm->get($id, '');
            $form->setTitle(sprintf(_("Edit \"%s\""), $alarm['title']));
            $vars->set('title', $alarm['title']);
            $vars->set('text', $alarm['text']);
            $vars->set('start', $alarm['start']->timestamp());
            if (!empty($alarm['end'])) {
                $vars->set('end', $alarm['end']->timestamp());
            }
            $vars->set('methods', $alarm['methods']);
            foreach ($alarm['params'] as $method => $params) {
                foreach ($params as $name => $value) {
                    $vars->set($method . '_' . $name, $value);
                }
            }
        } catch (Horde_Alarm_Exception $e) {
            $notification->push($alarm);
            $id = $alarm = null;
        }
    }
}

try {
    $alarms = $horde_alarm->globalAlarms();
} catch (Horde_Alarm_Exception $e) {
    $alarms = $e;
}

$title = _("Alarms");
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

echo '<h1 class="header">' . _("Current Alarms");
if ($alarms instanceof Exception) {
    echo '</h1><p class="headerbox"><em>' . sprintf(_("Listing alarms failed: %s"), $alarms->getMessage()) . '</em></p>';
} else {
    echo ' (' . count($alarms) . ')</h1>';
    echo '<ul class="headerbox linedRow">';
    foreach ($alarms as $alarm_details) {
        $url = Horde_Util::addParameter(Horde::url('alarms.php'), 'alarm', $alarm_details['id']);
        echo '<li>' . Horde::link(Horde_Util::addParameter($url, 'delete', 1), sprintf(_("Delete \"%s\""), $alarm_details['title']), '', '', 'return confirm(\'' . addslashes(sprintf(_("Are you sure you want to delete '%s'?"), $alarm_details['title'])) . '\')') . Horde::img('delete.png') . '</a> ' . Horde::link($url) . htmlspecialchars($alarm_details['title']) . '</a></li>';
    }
    echo '</ul>';
}

echo '<br />';

$form->renderActive();

require HORDE_TEMPLATES . '/common-footer.inc';
