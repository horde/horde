<input type="hidden" name="remote_action" id="remote_action" />
<input type="hidden" name="remote_data" id="remote_data" />

<table class="remotemanagement">
<?php if ($this->new): ?>
 <tr>
  <td class="item"><?php echo _("Label") ?>:</td>
  <td class="item">
   <input name="remote_label" size="30" />
  </td>
  <td></td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Type") ?>:</td>
  <td class="item">
   <select name="remote_type">
    <option value="imap" selected="selected"><?php echo _("IMAP") ?></option>
    <option value="pop3"><?php echo _("POP3") ?></option>
   </select>
  </td>
  <td class="required"><?php echo Horde::img('required.png', '*') ?></td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Server") ?>:</td>
  <td class="item">
   <input name="remote_server" size="30" />
  </td>
  <td class="required"><?php echo Horde::img('required.png', '*') ?></td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Username") ?>:</td>
  <td class="item">
   <input name="remote_user" size="30" />
  </td>
  <td class="required"><?php echo Horde::img('required.png', '*') ?></td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Port") ?>:</td>
  <td class="item">
   <input name="remote_port" size="10" />
  </td>
  <td></td>
 </tr>
 <tr>
  <td class="item"><?php echo _("Use secure connection?") ?></td>
  <td class="item">
   <select name="remote_secure">
    <option value="yes" selected="selected"><?php echo _("Required") ?></option>
    <option value="auto"><?php echo _("Use if available") ?></option>
    <option value="no"><?php echo _("Never") ?></option>
   </select>
  </td>
  <td></td>
 </tr>
</table>

<input id="add_button" type="button" class="button" value="<?php echo _("Save") ?>" />
<input id="cancel_button" type="button" class="button" value="<?php echo _("Cancel") ?>" />
<?php else: ?>
 <thead>
  <tr>
   <td><?php echo _("Label") ?></td>
   <td><?php echo _("Type") ?></td>
   <td><?php echo _("Server") ?></td>
   <td><?php echo _("Username") ?></td>
   <td><?php echo _("Port") ?></td>
   <td><?php echo _("Secure") ?></td>
   <td></td>
  </tr>
 </thead>
 <tbody>
<?php if ($this->accounts): ?>
<?php foreach ($this->accounts as $v): ?>
  <tr>
   <td><?php echo $this->h($v->label) ?></td>
   <td><?php echo ($v->type == IMP_Remote_Account::POP3) ? 'POP3' : 'IMAP' ?></td>
   <td><?php echo $this->h($v->hostspec) ?></td>
   <td><?php echo $this->h($v->username) ?></td>
   <td><?php echo $this->h($v->port) ?></td>
<?php if (is_null($v->secure)): ?>
   <td><?php echo _("Auto") ?></td>
<?php elseif ($v->secure): ?>
   <td class="remoteSecure"><?php echo _("Yes") ?></td>
<?php else: ?>
   <td class="remoteNotSecure"><?php echo _("No") ?></td>
<?php endif; ?>
   <td>
    <a class="remotedelete" href="#" data-id="<?php echo $v->id ?>"><?php echo $this->hordeImage('delete.png') ?></a>
   </td>
  </tr>
<?php endforeach; ?>
<?php else: ?>
  <tr>
   <td class="noneconfigured" colspan="3"><?php echo _("No remote accounts configured") ?></td>
  </tr>
<?php endif; ?>
 </tbody>
</table>

<input id="new_button" type="submit" class="button" value="<?php echo _("Add Account") ?>" />
<?php endif; ?>
