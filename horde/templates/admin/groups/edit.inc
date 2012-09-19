<form name="edit_group" method="post" action="groups.php">
<input type="hidden" name="actionID" value="editform" />
<input type="hidden" name="gid" value="<?php echo $gid ?>" />
<?php Horde_Util::pformInput() ?>

<h1 class="header">
 <?php echo Horde::img('user.png') . ' ' . htmlspecialchars($group['name']) ?>
</h1>

<?php if (!$groups->readOnly()): ?>
<p class="horde-content">
 <?php echo Horde::label('email', _("Email Address")) ?>
 <input type="text" name="email" id="email" size="50" value="<?php echo htmlspecialchars($group['email']) ?>" />
</p>
<?php endif; ?>

<h1 class="header">
 <?php echo Horde::img('group.png') . ' ' . _("Members") ?>
</h1>

<table class="linedRow nowrap" cellspacing="0" width="100%">
 <tr class="control">
  <th width="10%" class="leftAlign"><?php echo _("User") ?></th>
  <?php if (!$groups->readOnly()): ?>
  <th width="90%" class="leftAlign"><?php echo _("Remove") ?></th>
  <?php endif; ?>
 </tr>
<?php foreach ($users as $user): ?>
 <tr>
  <td><?php echo Horde::label('remove_' . htmlspecialchars($user), htmlspecialchars($user), '') ?></td>
  <?php if (!$groups->readOnly()): ?>
  <td><input type="checkbox" id="remove_<?php echo htmlspecialchars($user) ?>" name="remove[<?php echo htmlspecialchars($user) ?>]" /></td>
  <?php endif; ?>
 </tr>
<?php endforeach; ?>
</table>

<?php if (count($inherited_users)): ?>
<h1 class="header">
 <?php echo Horde::img('group.png') . ' ' . _("Inherited Members") ?>
</h1>
<div class="horde-content">
 <ul>
<?php foreach ($inherited_users as $user): ?>
  <li><?php echo htmlspecialchars($user) ?></li>
<?php endforeach; ?>
 </ul>
</div>
<?php endif; ?>

<?php if (!$groups->readOnly()): ?>
<h1 class="header">
 <?php echo Horde::img('user.png') . ' ' . _("Add Members") ?>
</h1>
<p class="horde-content">
<?php if ($auth->hasCapability('list')): ?>
 <label for="new_user" class="hidden"><?php echo _("Users") ?></label>
 <select id="new_user" name="new_user[]" multiple="multiple">
<?php foreach ($user_list as $user): ?>
<?php if (!in_array($user, $users)): ?>
  <option value="<?php echo $user ?>"><?php echo $user ?></option>
<?php endif ?>
<?php endforeach ?>
 </select>
<?php else: ?>
 <label for="new_user" class="hidden"><?php echo _("User") ?></label>
 <input type="text" id="new_user" name="new_user" />
<?php endif ?>
 <input type="submit" class="horde-default" value="<?php echo htmlspecialchars(sprintf(_("Save \"%s\""), $group['name'])) ?>" />
</p>
<?php endif ?>

</form>
