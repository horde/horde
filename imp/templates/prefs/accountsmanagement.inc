<input type="hidden" name="accounts_action" id="accounts_action" />
<input type="hidden" name="accounts_data" id="accounts_data" />

<?php if (Horde_Util::getFormData('accounts_action') == 'new'): ?>
<table class="accountsmanagement">
 <tr>
  <td class="item"><?php echo _("Type") ?>:</td>
  <td class="item">
   <select name="accounts_type">
    <option value="imap" selected="selected"><?php echo _("IMAP") ?></option>
    <option value="pop3"><?php echo _("POP3") ?></option>
   </select>
  </td>
  <td class="required">*</td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Server") ?>:</td>
  <td class="item"><input name="accounts_server" size="30"/></td>
  <td class="required">*</td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Username") ?>:</td>
  <td class="item"><input name="accounts_username" size="30"/></td>
  <td class="required">*</td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Port") ?>:</td>
  <td class="item"><input name="accounts_port" size="10"/></td>
  <td></td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Use secure connection?") ?></td>
  <td class="item">
   <select name="accounts_secure">
    <option value="yes" selected="selected"><?php echo _("Required") ?></option>
    <option value="auto" selected="selected"><?php echo _("Use if available") ?></option>
    <option value="no" selected="selected"><?php echo _("Never") ?></option>
   </select>
  </td>
  <td></td>
 </tr>
</table>

<input id="add_button" type="button" class="button" value="<?php echo _("Save") ?>" />
<input id="cancel_button" type="button" class="button" value="<?php echo _("Cancel") ?>" />
<?php else: ?>
<?php
$imp_accounts = IMP_Accounts::singleton();
$accounts_list = $imp_accounts->getList();
$delete_img = Horde::img('delete.png');
?>

<table class="accountsmanagement">
 <thead>
  <tr>
   <td><?php echo _("Server") ?></td>
   <td><?php echo _("Type") ?></td>
   <td><?php echo _("Port") ?></td>
   <td><?php echo _("Secure") ?>?</td>
   <td></td>
  </tr>
 </thead>
 <tbody>
<?php if (empty($accounts_list)): ?>
  <tr>
   <td class="noneconfigured" colspan="3"><?php echo _("No accounts configured") ?></td>
  </tr>
<?php else: ?>
<?php foreach ($accounts_list as $key => $val): ?>
  <tr id="accountsid_<?php echo $key ?>">
   <td><?php echo htmlspecialchars($val['server']) ?></td>
   <td><?php echo htmlspecialchars($val['type']) ?></td>
   <td><?php echo htmlspecialchars($val['port']) ?></td>
<?php if ($val['secure'] == 'auto'): ?>
   <td><?php echo _("Auto") ?></td>
<?php elseif ($val['secure'] == 'yes'): ?>
   <td class="accountsSecure"><?php echo _("Yes") ?></td>
<?php else: ?>
   <td class="accountsNotSecure"><?php echo _("No") ?></td>
<?php endif; ?>
   <td><a class="accountsdelete" href="#"><?php echo $delete_img ?></a></td>
  </tr>
<?php endforeach; ?>
<?php endif; ?>
 </tbody>
</table>

<input id="new_button" type="submit" class="button" value="<?php echo _("Add Account") ?>" />
<?php endif; ?>
