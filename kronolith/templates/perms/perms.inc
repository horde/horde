<form name="edit" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="editform" />
<input type="hidden" name="cid" value="<?php echo htmlspecialchars($share->getId()) ?>" />
<table width="100%" cellspacing="0">
<tr valign="middle">
  <td width="100%" colspan="7" class="header">
    <span class="rightFloat">
      <a href="#" onclick="javascript:window.close();" style="cursor:pointer"><?php echo Horde::img('close.png', _("Close Window"), '', $registry->getImageDir('horde')) ?></a>
    </span>
    <?php echo Horde::img('perms.png', '', '', $registry->getImageDir('horde')) . '&nbsp;' . sprintf(_("Edit permissions for %s"), htmlspecialchars($share->get('name'))) . '&nbsp;' . Horde_Help::link('kronolith', 'shares') ?>
  </td>
</tr>
<tr><td colspan="7">&nbsp;</td></tr>

<?php $owner = $share->get('owner'); ?>
<?php $userperms = $perm->getUserPermissions(); ?>

<!-- Ownership -->
<tr valign="middle">
  <td class="header leftAlign" colspan="7">
    <?php echo Horde::img('perms.png', '', '', $registry->getImageDir('horde')) . '&nbsp;' . _("Owner") ?>
  </td>
</tr>
<tr>
<?php if ($auth->hasCapability('list')): ?>
  <td class="light" colspan="7">
    <label for="owner" class="hidden"><?php echo _("Owner:") ?></label>
    <select id="owner" name="owner">
      <option value=""><?php echo _("Select a new owner:") ?></option>
      <option value="<?php echo htmlspecialchars(Auth::removeHook($owner)) ?>" selected="selected"><?php echo htmlspecialchars(Auth::removeHook($owner)) ?></option>
<?php foreach ($userList as $user) { if ($user != Auth::removeHook($owner)) { ?>
      <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
<?php } } ?>
    </select>
  </td>
<?php else: ?>
  <td class="light">
    <label for="owner" class="hidden"><?php echo _("Owner:") ?></label>
    <input type="text" id="owner" name="owner" value="<?php echo htmlspecialchars(Auth::removeHook($owner)) ?>" />
  </td>
<?php endif; ?>
</tr>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Default Permissions -->
<tr valign="middle">
  <td class="header leftAlign" width="55%">
    <?php echo Horde::img('perms.png', '', '', $registry->getImageDir('horde')) . '&nbsp;' . _("All Authenticated Users") ?>
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
    <input type="checkbox" id="default_show" name="default_show"<?php echo ($dperm & PERMS_SHOW) ? ' checked="checked"' : '' ?> />
    <label for="default_show" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_read" name="default_read"<?php echo ($dperm & PERMS_READ) ? ' checked="checked"' : '' ?> />
    <label for="default_read" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_edit" name="default_edit"<?php echo ($dperm & PERMS_EDIT) ? ' checked="checked"' : '' ?> />
    <label for="default_edit" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_delete" name="default_delete"<?php echo ($dperm & PERMS_DELETE) ? ' checked="checked"' : '' ?> />
    <label for="default_delete" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="default_delegate" name="default_delegate"<?php echo ($dperm & PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
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
    <input type="checkbox" id="guest_show" name="guest_show"<?php echo ($gperm & PERMS_SHOW) ? ' checked="checked"' : '' ?> />
    <label for="guest_show" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_read" name="guest_read"<?php echo ($gperm & PERMS_READ) ? ' checked="checked"' : '' ?> />
    <label for="guest_read" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_edit" name="guest_edit"<?php echo ($gperm & PERMS_EDIT) ? ' checked="checked"' : '' ?> />
    <label for="guest_edit" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_delete" name="guest_delete"<?php echo ($gperm & PERMS_DELETE) ? ' checked="checked"' : '' ?> />
    <label for="guest_delete" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="guest_delegate" name="guest_delegate"<?php echo ($gperm & PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="guest_delegate" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Creator Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo Horde::img('user.png', '', '', $registry->getImageDir('horde')) . '&nbsp;' . _("Object Creator") ?>
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
    <input type="checkbox" id="creator_show"  name="creator_show"<?php echo ($cperm & PERMS_SHOW) ? ' checked="checked"' : '' ?> />
    <label for="creator_show" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_read" name="creator_read"<?php echo ($cperm & PERMS_READ) ? ' checked="checked"' : '' ?> />
    <label for="creator_read" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_edit" name="creator_edit"<?php echo ($cperm & PERMS_EDIT) ? ' checked="checked"' : '' ?> />
    <label for="creator_edit" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_delete" name="creator_delete"<?php echo ($cperm & PERMS_DELETE) ? ' checked="checked"' : '' ?> />
    <label for="creator_delete" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="creator_delegate" name="creator_delegate"<?php echo ($cperm & PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="creator_delegate" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- User Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo Horde::img('user.png', '', '', $registry->getImageDir('horde')) . '&nbsp;' . _("Individual Users") ?>
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
        $user = Auth::removeHook($user);
?>
<tr>
  <td class="light"><?php echo htmlspecialchars($user) ?><input type="hidden" name="u_names[<?php echo htmlspecialchars($user) ?>]" value="<?php echo htmlspecialchars($user) ?>" /></td>
  <td align="center">
    <input type="checkbox" id="u_show_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_show[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & PERMS_SHOW) ? ' checked="checked"' : '' ?> />
    <label for="u_show_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_read_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_read[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & PERMS_READ) ? ' checked="checked"' : '' ?> />
    <label for="u_read_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_edit_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_edit[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & PERMS_EDIT) ? ' checked="checked"' : '' ?> />
    <label for="u_edit_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delete_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_delete[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & PERMS_DELETE) ? ' checked="checked"' : '' ?> />
    <label for="u_delete_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delegate_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" name="u_delegate[<?php echo htmlspecialchars($user) ?>]"<?php echo ($uperm & PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
    <label for="u_delegate_<?php echo str_replace('@', '_', htmlspecialchars($user)) ?>" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>
<?php } } ?>
<!-- New user row -->
<tr>
<?php if ($auth->hasCapability('list')): ?>
  <td class="light">
    <label for="u_names_new" class="hidden"><?php echo _("Select a user to add:") ?></label>
    <select id="u_names_new" name="u_names[||new]">
      <option value=""><?php echo _("Select a user to add:") ?></option>
<?php
foreach ($userList as $user) {
    if (!isset($userperms[Auth::addHook($user)]) &&
        $user != Auth::removeHook($owner)) {
?>
      <option value="<?php echo htmlspecialchars($user) ?>"><?php echo htmlspecialchars($user) ?></option>
<?php } } ?>
    </select>
  </td>
<?php else: ?>
  <td class="light">
    <label for="u_names_new" class="hidden"><?php echo _("User to add:") ?></label>
    <input type="text" id="u_names_new" name="u_names[||new]" />
  </td>
<?php endif; ?>
  <td align="center">
    <input type="checkbox" id="u_show_new" name="u_show[||new]" />
    <label for="u_show_new" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_read_new" name="u_read[||new]" />
    <label for="u_read_new" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_edit_new" name="u_edit[||new]" />
    <label for="u_edit_new" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delete_new" name="u_delete[||new]" />
    <label for="u_delete_new" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="u_delegate_new" name="u_delegate[||new]" />
    <label for="u_delegate_new" class="hidden"><?php echo _("Delegate") ?></label>
  </td>
  <td>&nbsp;</td>
</tr>

<!-- Spacer -->
<tr><td colspan="7">&nbsp;</td></tr>

<!-- Group Permissions -->
<tr valign="middle">
  <td class="header leftAlign">
    <?php echo Horde::img('group.png', '', '', $registry->getImageDir('horde')) . '&nbsp;' . _("Groups") ?>
  </td>
  <td class="header" align="center"><?php echo _("Show") ?></td>
  <td class="header" align="center"><?php echo _("Read") ?></td>
  <td class="header" align="center"><?php echo _("Edit") ?></td>
  <td class="header" align="center"><?php echo _("Delete") ?></td>
  <td class="header" align="center"><?php echo _("Delegate") ?></td>
  <td class="header">&nbsp;</td>
</tr>
<?php $groupperms = $perm->getGroupPermissions(); foreach ($groupperms as $group => $gperm): if (!$groups->exists($groups->getGroupName($group))) continue; ?>
<tr>
  <td class="light"><?php echo htmlspecialchars($groups->getGroupName($group)) ?><input type="hidden" name="g_names[<?php echo htmlspecialchars($group) ?>]" value="<?php echo htmlspecialchars($group) ?>" /></td>
  <td align="center">
    <input type="checkbox" id="g_show_<?php echo htmlspecialchars($group) ?>" name="g_show[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & PERMS_SHOW) ? ' checked="checked"' : '' ?> />
    <label for="g_show_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Show") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_read_<?php echo htmlspecialchars($group) ?>" name="g_read[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & PERMS_READ) ? ' checked="checked"' : '' ?> />
    <label for="g_read_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Read") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_edit_<?php echo htmlspecialchars($group) ?>" name="g_edit[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & PERMS_EDIT) ? ' checked="checked"' : '' ?> />
    <label for="g_edit_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Edit") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_delete_<?php echo htmlspecialchars($group) ?>" name="g_delete[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & PERMS_DELETE) ? ' checked="checked"' : '' ?> />
    <label for="g_delete_<?php echo htmlspecialchars($group) ?>" class="hidden"><?php echo _("Delete") ?></label>
  </td>
  <td align="center">
    <input type="checkbox" id="g_delegate_<?php echo htmlspecialchars($group) ?>" name="g_delegate[<?php echo htmlspecialchars($group) ?>]"<?php echo ($gperm & PERMS_DELEGATE) ? ' checked="checked"' : '' ?> />
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
