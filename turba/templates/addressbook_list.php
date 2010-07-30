<h1 class="header">
 <?php echo _("Manage Address Books") ?>
</h1>

<div id="addressbook-list-buttons">
 <form method="get" action="create.php">
<?php echo Horde_Util::formInput() ?>
  <input type="submit" class="button" value="<?php echo _("Create a new Address Book") ?>" />
 </form>
</div>

<table summary="<?php echo _("Address Book List") ?>" cellspacing="0" id="addressbook-list" class="striped sortable">
 <thead>
  <tr>
   <th class="addressbook-list-icon nosort"><?php echo $browse_img ?></th>
<th class="sortdown"><?php echo _("Address Book") ?></th>
   <th class="addressbook-list-icon nosort"><?php echo $edit_img ?></th>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <th class="addressbook-list-icon nosort"><?php echo $perms_img ?></th>
<?php endif; ?>
   <th class="addressbook-list-icon nosort"><?php echo $delete_img ?></th>
  </tr>
 </thead>

 <tbody>
<?php foreach (array_keys($sorted_addressbooks) as $addressbook_id): ?>
 <?php $addressbook = $addressbooks[$addressbook_id] ?>
  <tr>
   <td><a href="<?php echo $browse_url_base->copy()->add('source', $addressbook->getName()) ?>" title="<?php echo _("Browse") ?>"><?php echo $browse_img ?></a></td>
   <td><?php echo htmlspecialchars($addressbook->get('name')) ?></td>
   <td><a href="<?php echo $edit_url_base->copy()->add('a', $addressbook->getName()) ?>" title="<?php echo _("Edit") ?>"><?php echo $edit_img ?></a></td>
<?php if (empty($conf['share']['no_sharing'])): ?>
   <td><?php echo $perms_url_base->add('share', $addressbook->getName())->link(array('title' => _("Change Permissions"), 'target' => '_blank', 'onclick' => Horde::popupJs($perms_url_base, array('params' => array('share' => $addressbook->getName()), 'urlencode' => true)) . 'return false;')) . $perms_img . '</a>';?>
<?php endif; ?>
   <td><a href="<?php echo $delete_url_base->copy()->add('a', $addressbook->getName()) ?>" title="<?php echo _("Delete") ?>"><?php echo $delete_img ?></a></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
