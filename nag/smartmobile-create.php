<?php
/**
 * Copyright 2001-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author Jon Parise <jon@horde.org>
 * @author Jan Schneider <jan@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('nag');

$vars = Horde_Variables::getDefaultVariables();
$perms = $GLOBALS['injector']->getInstance('Horde_Core_Perms');
if ($perms->hasAppPermission('max_tasks') !== true &&
    $perms->hasAppPermission('max_tasks') <= Nag::countTasks()) {
    Horde::permissionDeniedError(
        'nag',
        'max_tasks',
        sprintf(_("You are not allowed to create more than %d tasks."), $perms->hasAppPermission('max_tasks'))
    );
    Horde::url('list.php', true)->redirect();
}

if (!$vars->exists('tasklist_id')) {
    $vars->set('tasklist_id', Nag::getDefaultTasklist(Horde_Perms::EDIT));
}
$vars->mobile = true;
$vars->url = Horde::url('smartmobile.php');
$form = new Nag_Form_Task($vars, _("New Task"), $mobile = true);

$page_output->header(array(
    'title' => $form->getTitle(),
    'view' => $registry::VIEW_SMARTMOBILE
));

?>
</head>
<body>
<div data-role="page" id="nag-task-view">
 <div data-role="header">
  <h1><?php echo htmlspecialchars($title) ?></h1>
 </div>
 <div data-role="content">
  <?php $form->renderActive(); ?>
 </div>
</div>
<?php

$page_output->footer();
