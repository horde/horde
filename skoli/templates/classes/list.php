<h1 class="header">
 <?php echo _("Manage Classes") ?>
</h1>

<div id="class-list-buttons">
 <form method="get" action="create.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Class") ?>" />
 </form>
</div>

<?php if (count($sorted_classes) > 0): ?>
<table summary="<?php echo _("Class List") ?>" cellspacing="0" id="class-list" class="striped sortable">
 <thead>
  <tr>
   <th class="sortdown"><?php echo _("Class") ?></th>
   <th class="class-list-icon nosort" colspan="<?php echo empty($conf['share']['no_sharing']) ? 3 : 2 ?>">&nbsp;</th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_classes) as $class_id): ?>
 <?php $class = $classes[$class_id] ?>
  <tr>
   <td><?php echo htmlspecialchars($class->get('name')) ?></td>
   <td><a href="<?php echo Horde_Util::addParameter($edit_url_base, 'c', $class->getName()) ?>" title="<?php echo _("Edit") ?>"><?php echo $edit_img ?></a></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><a onclick="<?php echo Horde::popupJs($perms_url_base, array('params' => array('share' => $class->getName()), 'urlencode' => true)) ?>return false;" href="<?php echo Horde_Util::addParameter($perms_url_base, 'share', $class->getName()) ?>" target="_blank" title="<?php echo _("Change Permissions") ?>"><?php echo $perms_img ?></a></td>
<?php endif; ?>
   <td><a href="<?php echo Horde_Util::addParameter($delete_url_base, 'c', $class->getName()) ?>" title="<?php echo _("Delete") ?>"><?php echo $delete_img ?></a></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>
