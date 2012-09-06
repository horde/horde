<?php
global $prefs, $registry;

$nag_webroot = $registry->get('webroot');
$horde_webroot = $registry->get('webroot', 'horde');

// Nag::VIEW_* constant
switch ($prefs->getValue('show_completed')) {
case Nag::VIEW_INCOMPLETE:
    $show_completed = 'incomplete';
    break;

case Nag::VIEW_ALL:
    $show_completed = 'all';
    break;

case Nag::VIEW_COMPLETE:
    $show_completed = 'complete';
    break;

case Nag::VIEW_FUTURE:
    $show_completed = 'future';
    break;

case Nag::VIEW_FUTURE_INCOMPLETE:
    $show_completed = 'future-incomplete';
    break;
}

$code['conf'] = array(
    'showCompleted' => $show_completed,
);

$code['conf']['icons'] = array(
    'completed' => (string)Horde_Themes::img('checked.png'),
    'uncompleted' => (string)Horde_Themes::img('unchecked.png')
);


echo $GLOBALS['page_output']->addInlineJsVars(array(
    'var Nag' => $code
), array('top' => true));