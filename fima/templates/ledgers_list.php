<h1 class="header">
 <?php echo _("Manage Ledgers") ?>
</h1>

<div id="ledger-list-buttons">
 <form method="get" action="create.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Ledger") ?>" />
 </form>
</div>

<table summary="<?php echo _("Ledger List") ?>" cellspacing="0" id="ledger-list" class="striped sortable">
 <thead>
  <tr>
   <th class="ledger-list-icon nosort"><?php echo $browse_img ?></th>
<th class="sortdown"><?php echo _("Ledger") ?></th>
   <th class="ledger-list-icon nosort"><?php echo $edit_img ?></th>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <th class="ledger-list-icon nosort"><?php echo $perms_img ?></th>
<?php endif; ?>
   <th class="ledger-list-icon nosort"><?php echo $delete_img ?></th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_ledgers) as $ledger_id): ?>
 <?php $ledger = $ledgers[$ledger_id] ?>
  <tr>
   <td><?php echo $browse_img ?></td>
   <td><?php echo htmlspecialchars($ledger->get('name')) ?></td>
   <td><a href="<?php echo Horde_Util::addParameter($edit_url_base, 'l', $ledger->getName()) ?>" title="<?php echo _("Edit") ?>"><?php echo $edit_img ?></a></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><a onclick="<?php echo Horde::popupJs($perms_url_base, array('params' => array('share' => $ledger->getName()), 'urlencode' => true)) ?>return false;" href="<?php echo Horde_Util::addParameter($perms_url_base, 'share', $ledger->getName()) ?>" target="_blank" title="<?php echo _("Change Permissions") ?>"><?php echo $perms_img ?></a></td>
<?php endif; ?>
   <td><a href="<?php echo Horde_Util::addParameter($delete_url_base, 'l', $ledger->getName()) ?>" title="<?php echo _("Delete") ?>"><?php echo $delete_img ?></a></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
