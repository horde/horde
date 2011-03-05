<form name="edit" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="editform" />
<input type="hidden" name="cid" value="<?php echo htmlspecialchars($share->getId()) ?>" />
<table width="100%" cellspacing="0">
<tr valign="middle">
  <td width="100%" colspan="7" class="header">
    <span class="rightFloat">
      <a href="#" onclick="javascript:window.close();" style="cursor:pointer"><?php echo Horde::img('close.png', _("Close Window")) ?></a>
    </span>
    <?php echo Horde::img('perms.png') . '&nbsp;' . sprintf(_("Edit permissions for %s"), htmlspecialchars($share->get('name'))) . '&nbsp;' . Horde_Help::link('kronolith', 'shares') ?>
  </td>
</tr>
<tr><td colspan="7">&nbsp;</td></tr>

<?php $owner = $share->get('owner'); ?>
<?php $userperms = $perm->getUserPermissions(); ?>

<!-- Ownership -->
<tr valign="middle">
  <td class="header leftAlign" colspan="7">
    <?php echo Horde::img('perms.png') . '&nbsp;' . _("Owner") ?>
  </td>
</tr>
<tr>
  <td class="light" colspan="7">
<?php if ($auth->hasCapability('list') && ($GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
    <label for="owner_select" class="hidden"><?php echo _("Owner:") ?></label>
    <select id="owner_select" name="owner_select">
      <option value=""><?php echo _("Select a new owner:") ?></option>
      <option value="<?php echo htmlspecialchars($registry->convertUsername($owner, false)) ?>" selected="selected"><?php echo htmlspecialchars($registry->convertUsername($owner, false)) ?></option>
<?php foreach ($userList as $user) { if ($user != $registry->convertUsername($owner, false)) { ?>
      <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
<?php } } ?>
    </select>
<?php else: ?>
    <label for="owner_input" class="hidden"><?php echo _("Owner:") ?></label>
    <input type="text" id="owner_input" name="owner_input" value="<?php echo htmlspecialchars($registry->convertUsername($owner, false)) ?>" />
<?php endif; ?>
  </td>
</tr>

<?php if ($GLOBALS['registry']->isAdmin() || !empty($GLOBALS['conf']['share']['world'])): ?>
<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Default Permissions -->
<tr valign="middle">
  <td class="header leftAlign" width="55%">
    <?php echo Horde::img('perms.png') . '&nbsp;' . _("All Authenticated Users") ?>
  </td>
  <td class="header" align="center"><?php echo _("Show") ?></td>
  <td class="header" align="center"><?php echo _("Read") ?></td>
  <td class="header" align="center"><?php echo _("Edit") ?></td>
  <td class="header" align="center"><?php echo _("Delete") ?></td>
  <td class="header" align="center"><?php echo _("Delegate") ?></td>
  <td class="header">&nbsp;</td>
</tr>
<?php $dperm = $perm->getDefaultPermissions(); ?>
<tr>
  <td class="light">&nbsp;</td>
  <td align="center">
    <input type="checkbox" id="default_show" name="default_show"<?php echo ($dperm & Horde_Perms::SHOW) ? ' checked="checked"' : '' ?> />
    <label for="default_show" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_read" name="default_read"<?php echo ($dperm & Horde_Perms::READ) ? ' checked="checked"' : '' ?> />
    <label for="default_read" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_edit" name="default_edit"<?php echo ($dperm & Horde_Perms::EDIT) ? ' checked="checked"' : '' ?> />
    <label for="default_edit" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_delete" name="default_delete"<?php echo ($dperm & Horde_Perms::DELETE) ? ' checked="checked"' : '' ?> />
    <label for="default_delete" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_delegate" name="default_delegate"<?php echo ($dperm & Kronolith::PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="default_delegate" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Guest Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo _("Guest Permissions") ?>
  </td>
  <td class="header" align="center"><?php echo _("Show") ?></td>
  <td class="header" align="center"><?php echo _("Read") ?></td>
  <td class="header" align="center"><?php echo _("Edit") ?></td>
  <td class="header" align="center"><?php echo _("Delete") ?></td>
  <td class="header" align="center"><?php echo _("Delegate") ?></td>
  <td class="header">&nbsp;</td>
</tr>
<?php $gperm = $perm->getGuestPermissions(); ?>
<tr>
  <td class="light">&nbsp;</td>
  <td align="center">
    <input type="checkbox" id="guest_show" name="guest_show"<?php echo ($gperm & Horde_Perms::SHOW) ? ' checked="checked"' : '' ?> />
    <label for="guest_show" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_read" name="guest_read"<?php echo ($gperm & Horde_Perms::READ) ? ' checked="checked"' : '' ?> />
    <label for="guest_read" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_edit" name="guest_edit"<?php echo ($gperm & Horde_Perms::EDIT) ? ' checked="checked"' : '' ?> />
    <label for="guest_edit" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_delete" name="guest_delete"<?php echo ($gperm & Horde_Perms::DELETE) ? ' checked="checked"' : '' ?> />
    <label for="guest_delete" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_delegate" name="guest_delegate"<?php echo ($gperm & Kronolith::PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="guest_delegate" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>
<?php endif; ?>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Creator Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo Horde::img('user.png') . '&nbsp;' . _("Object Creator") ?>
  </td>
  <td class="header" align="center"><?php echo _("Show") ?></td>
  <td class="header" align="center"><?php echo _("Read") ?></td>
  <td class="header" align="center"><?php echo _("Edit") ?></td>
  <td class="header" align="center"><?php echo _("Delete") ?></td>
  <td class="header" align="center"><?php echo _("Delegate") ?></td>
  <td class="header"></td>
</tr>
<?php $cperm = $perm->getCreatorPermissions(); ?>
<tr>
  <td class="light">&nbsp;</td>
  <td align="center">
    <input type="checkbox" id="creator_show"  name="creator_show"<?php echo ($cperm & Horde_Perms::SHOW) ? ' checked="checked"' : '' ?> />
    <label for="creator_show" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_read" name="creator_read"<?php echo ($cperm & Horde_Perms::READ) ? ' checked="checked"' : '' ?> />
    <label for="creator_read" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_edit" name="creator_edit"<?php echo ($cperm & Horde_Perms::EDIT) ? ' checked="checked"' : '' ?> />
    <label for="creator_edit" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_delete" name="creator_delete"<?php echo ($cperm & Horde_Perms::DELETE) ? ' checked="checked"' : '' ?> />
    <label for="creator_delete" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_delegate" name="creator_delegate"<?php echo ($cperm & Kronolith::PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="creator_delegate" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- User Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo Horde::img('user.png') . '&nbsp;' . _("Individual Users") ?>
  </td>
  <td class="header" align="center"><?php echo _("Show") ?></td>
  <td class="header" align="center"><?php echo _("Read") ?></td>
  <td class="header" align="center"><?php echo _("Edit") ?></td>
  <td class="header" align="center"><?php echo _("Delete") ?></td>
  <td class="header" align="center"><?php echo _("Delegate") ?></td>
  <td class="header"></td>
</tr>
<?php
foreach ($userperms as $user => $uperm) {
    if ($user != $owner) {
        $user = $registry->convertUsername($user, false);
?>
<tr>
  <td class="light"><?php echo htmlspecialchars($user) ?><input type="hidden" name="u_names[<?php echo htmlspecialchars($user) ?>]" value="<?php echo htmlspecialchars($user) ?>" /></td>
  <td align="center">
    <input type="checkbox" id="u_show_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_show[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & Horde_Perms::SHOW) ? ' checked="checked"' : '' ?> />
    <label for="u_show_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_read_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_read[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & Horde_Perms::READ) ? ' checked="checked"' : '' ?> />
    <label for="u_read_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_edit_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_edit[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & Horde_Perms::EDIT) ? ' checked="checked"' : '' ?> />
    <label for="u_edit_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delete_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_delete[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & Horde_Perms::DELETE) ? ' checked="checked"' : '' ?> />
    <label for="u_delete_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delegate_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_delegate[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & Kronolith::PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="u_delegate_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>
<?php } } ?>

<!-- New user rows -->
<?php if ($auth->hasCapability('list') && (!isset($GLOBALS['conf']['auth']['list_users']) || $GLOBALS['conf']['auth']['list_users'] == 'list' || $GLOBALS['conf']['auth']['list_users'] == 'both')): ?>
<tr>
  <td class="light">
    <label for="u_names_new_select" class="hidden"><?php echo _("Select a user to add:") ?></label>
    <select id="u_names_new_select" name="u_names[||new_select]">
      <option value=""><?php echo _("Select a user to add:") ?></option>
<?php
foreach ($userList as $user) {
    if (!isset($userperms[$registry->convertUsername($user, true)]) &&
        $user != $registry->convertUsername($owner, false)) {
?>
      <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
<?php } } ?>
    </select>
  </td>
  <td align="center">
    <input type="checkbox" id="u_show_new_select" name="u_show[||new_select]" />
    <label for="u_show_new_select" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_read_new_select" name="u_read[||new_select]" />
    <label for="u_read_new_select" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_edit_new_select" name="u_edit[||new_select]" />
    <label for="u_edit_new_select" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delete_new_select" name="u_delete[||new_select]" />
    <label for="u_delete_new_select" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delegate_new_select" name="u_delegate[||new_select]" />
    <label for="u_delegate_new_select" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
</tr>
<?php endif; ?>
<?php if (!$auth->hasCapability('list') || !isset($GLOBALS['conf']['auth']['list_users']) || $GLOBALS['conf']['auth']['list_users'] == 'input' || $GLOBALS['conf']['auth']['list_users'] == 'both'): ?>
<tr>
  <td class="light">
<?php if ($auth->hasCapability('list') && isset($GLOBALS['conf']['auth']['list_users']) && $GLOBALS['conf']['auth']['list_users'] == 'both'): ?>
    <?php echo _("Or enter a user name:") ?>
<?php endif; ?>
    <label for="u_names_new_input" class="hidden"><?php echo _("User to add:") ?></label>
    <input type="text" id="u_names_new_input" name="u_names[||new_input]" />
  </td>
  <td align="center">
    <input type="checkbox" id="u_show_new_input" name="u_show[||new_input]" />
    <label for="u_show_new_input" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_read_new_input" name="u_read[||new_input]" />
    <label for="u_read_new_input" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_edit_new_input" name="u_edit[||new_input]" />
    <label for="u_edit_new_input" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delete_new_input" name="u_delete[||new_input]" />
    <label for="u_delete_new_input" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delegate_new_input" name="u_delegate[||new_input]" />
    <label for="u_delegate_new_input" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>
<?php endif; ?>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Group Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo Horde::img('group.png') . '&nbsp;' . _("Groups") ?>
  </td>
  <td class="header" align="center"><?php echo _("Show") ?></td>
  <td class="header" align="center"><?php echo _("Read") ?></td>
  <td class="header" align="center"><?php echo _("Edit") ?></td>
  <td class="header" align="center"><?php echo _("Delete") ?></td>
  <td class="header" align="center"><?php echo _("Delegate") ?></td>
  <td class="header">&nbsp;</td>
</tr>
<?php $groupperms = $perm->getGroupPermissions(); foreach ($groupperms as $group => $gperm): if (!$groups->exists($group)) continue; ?>
<tr>
  <td class="light"><?php echo htmlspecialchars($groups->getName($group)) ?><input type="hidden" name="g_names[<?php echo htmlspecialchars($group) ?>]" value="<?php echo htmlspecialchars($group) ?>" /></td>
  <td align="center">
    <input type="checkbox" id="g_show_<?php echo htmlspecialchars($group) ?>" name="g_show[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & Horde_Perms::SHOW) ? ' checked="checked"' : '' ?> />
    <label for="g_show_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_read_<?php echo htmlspecialchars($group) ?>" name="g_read[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & Horde_Perms::READ) ? ' checked="checked"' : '' ?> />
    <label for="g_read_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_edit_<?php echo htmlspecialchars($group) ?>" name="g_edit[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & Horde_Perms::EDIT) ? ' checked="checked"' : '' ?> />
    <label for="g_edit_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_delete_<?php echo htmlspecialchars($group) ?>" name="g_delete[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & Horde_Perms::DELETE) ? ' checked="checked"' : '' ?> />
    <label for="g_delete_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_delegate_<?php echo htmlspecialchars($group) ?>" name="g_delegate[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & Kronolith::PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="g_delegate_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>
<?php endforeach; ?>
<!-- New group row -->
<tr>
  <td class="light">
    <label for="g_names_new" class="hidden"><?php echo _("Select a group to add:") ?></label>
    <select id="g_names_new" name="g_names[||new]">
      <option value=""><?php echo _("Select a group to add") ?></option>
    <?php foreach ($groupList as $gid => $group) { if (empty($groupperms[$gid])) { ?>
      <option value="<?php echo htmlspecialchars($gid) ?>"><?php echo htmlspecialchars($group) ?></option>
    <?php } } ?>
    </select>
  </td>
  <td align="center">
    <input type="checkbox" id="g_show_new" name="g_show[||new]" />
    <label for="g_show_new" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_read_new" name="g_read[||new]" />
    <label for="g_read_new" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_edit_new" name="g_edit[||new]" />
    <label for="g_edit_new" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_delete_new" name="g_delete[||new]" />
    <label for="g_delete_new" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_delegate_new" name="g_delegate[||new]" />
    <label for="g_delegate_new" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>

<tr>
 <td colspan="7">&nbsp;</td>
</tr>

<tr>
 <td colspan="7">
  <input type="submit" class="button" value="<?php printf(_("Save %s"), htmlspecialchars($share->get('name'))) ?>" />
  <input type="submit" class="button" name="save_and_finish" value="<?php echo htmlspecialchars(_("Save and Finish")) ?>" />
 </td>
</tr>

</table>
</form>
<br />
