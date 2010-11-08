<h1 class="header">
 <?php echo _("Manage Task Lists") ?>
</h1>

<?php if (!$prefs->isLocked('default_tasklist')): ?>
<div id="tasklist-list-buttons">
 <form method="get" action="create.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Task List") ?>" />
 </form>
</div>
<?php endif; ?>

<table summary="<?php echo _("Task List List") ?>" cellspacing="0" id="tasklist-list" class="striped sortable">
 <thead>
  <tr>
   <th class="sortdown"><?php echo _("Task List") ?></th>
   <th><?php echo _("Kind") ?></th>
   <th class="tasklist-list-url nosort"><?php echo _("Display URL") ?></th>
   <th class="tasklist-list-url nosort"><?php echo _("Subscription URL") ?></th>
   <th class="tasklist-list-icon nosort" colspan="<?php echo empty($conf['share']['no_sharing']) ? 3 : 2 ?>">&nbsp;</th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_tasklists) as $tasklist_id): ?>
 <?php $tasklist = $tasklists[$tasklist_id] ?>
  <tr>
   <td><?php echo htmlspecialchars($tasklist->get('name')) ?></td>
   <td><?php echo is_null($tasklist->get('owner')) ? _("System") : _("Local") ?></td>
   <td><?php $url = Horde_Util::addParameter($display_url_base, 'display_tasklist', $tasklist->getName(), false); echo Horde::link($url, _("Click or copy this URL to display this task list"), '', '_blank') . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
   <td><?php $url = $subscribe_url_base . ($tasklist->get('owner') ? $tasklist->get('owner') : '-system-') . '/' . $tasklist->getName() . '.ics'; echo Horde::link($url, _("Click or copy this URL to display this task list"), '', '_blank') . htmlspecialchars(shorten_url($url)) . '</a>' ?></td>
   <td><?php echo Horde::link(Horde_Util::addParameter($edit_url_base, 't', $tasklist->getName()), _("Edit")) . $edit_img . '</a>' ?></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><?php echo Horde::link(Horde_Util::addParameter($perms_url_base, 'share', $tasklist->getName()), _("Change Permissions"), '', '_blank', Horde::popupJs($perms_url_base, array('params' => array('share' => $tasklist->getName()), 'urlencode' => true)) . 'return false;') . $perms_img . '</a>' ?></td>
<?php endif; ?>
   <td><?php echo Horde::link(Horde_Util::addParameter($delete_url_base, 't', $tasklist->getName()), _("Delete")) . $delete_img . '</a>' ?></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
