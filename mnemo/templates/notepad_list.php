<h1 class="header">
 <?php echo _("Manage Notepads") ?>
</h1>

<?php if (!$prefs->isLocked('default_notepad')): ?>
<div id="notepad-list-buttons">
 <form method="get" action="create.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Notepad") ?>" />
 </form>
</div>
<?php endif; ?>

<table summary="<?php echo _("Notepad List") ?>" cellspacing="0" id="notepad-list" class="striped sortable">
 <thead>
  <tr>
   <th class="sortdown"><?php echo _("Notepad") ?></th>
   <th class="notepad-list-icon nosort" colspan="<?php echo empty($conf['share']['no_sharing']) ? 3 : 2 ?>">&nbsp;</th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_notepads) as $notepad_id): ?>
 <?php $notepad = $notepads[$notepad_id] ?>
  <tr>
   <td><?php echo htmlspecialchars($notepad->get('name')) ?></td>
   <td><a href="<?php echo Horde_Util::addParameter($edit_url_base, 'n', $notepad->getName()) ?>" title="<?php echo _("Edit") ?>"><?php echo $edit_img ?></a></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><?php echo $perms_url_base->add('share', $notepad->getName())->link(array('target' => '_blank', 'title' => _("Change Permissions"), 'onclick' => Horde::popupJs($perms_url_base, array('params' => array('share' => $notepad->getName()), 'urlencode' => true)) . 'return false;')) . $perms_img . '</a>' ?></td>
<?php endif; ?>
   <td><a href="<?php echo Horde_Util::addParameter($delete_url_base, 'n', $notepad->getName()) ?>" title="<?php echo _("Delete") ?>"><?php echo $delete_img ?></a></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
