<div class="horde-buttonbar">
  <ul>
<?php if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)): ?>
<?php if (!$task->completed): ?>
    <li class="horde-icon"><?php echo Horde::widget(array('url' => $task->complete_link, 'title' => _("_Complete"), 'class' => 'nag-complete')) ?></li>
<?php endif ?>
<?php if (!$task->private || $task->owner == $GLOBALS['registry']->getAuth()): ?>
    <li class="horde-icon"><?php echo Horde::widget(array('url' => $taskurl->add('actionID', 'modify_task'), 'title' => _("_Edit"), 'class' => 'nag-edit')) ?></li>
<?php endif ?>
<?php endif ?>
<?php if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)): ?>
    <li class="horde-icon"><?php echo Horde::widget(array('url' => $taskurl->add('actionID', 'delete_task'), 'onclick' => $prefs->getValue('delete_opt') ? 'return window.confirm(\'' . addslashes(_("Really delete this task?")) . '\');' : '', 'title' => _("_Delete"), 'class' => 'nag-delete')) ?></li>
<?php endif ?>
  </ul>
</div>

<div class="header">
 <span class="smallheader rightFloat"><?php echo implode(' | ', $links) ?></span>
 <?php echo htmlspecialchars($task->name) ?>
</div>

<div class="horde-header">
<table cellspacing="0" width="100%">
 <tr>
  <td class="horde-label"><?php echo _("Assignee") ?>:</td>
  <td width="100%"><?php echo Nag::formatAssignee($task->assignee, true) ?></td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Private?") ?>:</td>
  <td>
   <?php echo $task->private ? Horde::img('checked.png', _("Private")) : Horde::img('unchecked.png', _("Not Private")) ?>
  </td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Start Date") ?>:</td>
  <td><?php echo Nag::formatDate($task->start, false) ?></td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Due By") ?>:</td>
  <td><?php echo Nag::formatDate($task->due) ?></td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Alarm") ?>:</td>
  <td><?php echo htmlspecialchars($alarm_text) ?></td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Priority") ?>:</td>
  <td><?php echo Nag::formatPriority($task->priority) ?></td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Estimated Time") ?>:</td>
  <td><?php echo htmlspecialchars($task->estimate); if ($task->hasSubTasks()) echo htmlspecialchars(sprintf(_(" (%s including sub-tasks)"), $task->estimation())) ?></td>
 </tr>
 <tr>
  <td class="horde-label"><?php echo _("Completed?") ?>:</td>
  <td>
   <?php echo Nag::formatCompletion($task->completed) ?>
   <?php if ($task->completed_date): ?>
   <?php echo strftime($prefs->getValue('date_format'), $task->completed_date) . ' ' . date($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a', $task->completed_date) ?>
   <?php endif ?>
  </td>
 </tr>

<?php if ($task->created): ?>
 <tr>
  <td class="horde-label"><?php echo _("Created") ?>:</td>
  <td><?php echo $task->created->strftime($prefs->getValue('date_format')) . ' ' . $task->created->format($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a') . ' ' . htmlspecialchars($task->createdby) ?></td>
 </tr>
<?php endif; ?>

<?php if ($task->modified): ?>
 <tr>
  <td class="horde-label"><?php echo _("Last Modified") ?>:</td>
  <td><?php echo $task->modified->strftime($prefs->getValue('date_format')) . ' ' . $task->modified->format($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a') . ' ' . htmlspecialchars($task->modifiedby) ?></td>
 </tr>
<?php endif; ?>
</table>
</div>

<?php if (strlen($task->desc)): ?>
<div class="horde-content taskBody">
  <?php echo $task->getFormattedDescription() ?>
</div>
<?php endif; ?>
