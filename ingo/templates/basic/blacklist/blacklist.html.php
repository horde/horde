<form method="post" name="filters" action="<?php echo $this->formurl ?>">
 <input type="hidden" name="actionID" value="rule_update" />

 <h1 class="header">
  <?php echo _("Blacklist") ?>
  <?php echo $this->hordeHelp('ingo', 'blacklist') ?>
<?php if ($this->disabled): ?>
  [<span style="color:red"><?php echo _("Disabled") ?></span>]
<?php endif; ?>
 </h1>

 <table>
  <tr>
   <td colspan="2">
    <em><?php echo _("Action for blacklisted addresses:") ?></em>
   </td>
  </tr>
  <tr>
   <td>
    <?php echo $this->radioButtonTag('action', 'delete', empty($this->folder), array('id' => 'action_delete')) ?>
    <?php echo $this->hordeLabel('action_delete', _("_Delete message completely")) ?>
    <br />
<?php if ($this->flagonly): ?>
    <?php echo $this->radioButtonTag('action', 'mark', $this->folder == Ingo::BLACKLIST_MARKER, array('id' => 'action_mark')) ?>
    <?php echo $this->hordeLabel('action_mark', _("Mar_k message as deleted")) ?>
    <br />
<?php endif; ?>
    <?php echo $this->radioButtonTag('action', 'folder', $this->folder && ($this->folder != Ingo::BLACKLIST_MARKER), array('id' => 'action_folder')) ?>
    <?php echo $this->hordeLabel('action_folder', _("_Move message to folder:")) ?>
    <label for="actionvalue" class="hidden"><?php echo _("Select target folder") ?></label>
    <?php echo $this->folderlist ?>
   </td>
   <td>
    <?php echo $this->hordeHelp('ingo', 'blacklist-action') ?>
   </td>
  </tr>
  <tr>
   <td colspan="2">
    <em><?php echo $this->hordeLabel('blacklist', _("_Enter each address on a new line:")) ?></em>
   </td>
  </tr>
  <tr>
   <td>
    <textarea name="blacklist" id="blacklist" rows="15" cols="80"><?php echo $this->h($this->blacklist) ?></textarea>
   </td>
   <td>
    <?php echo $this->hordeHelp('ingo', 'blacklist-addresses') ?>
   </td>
  </tr>
  <tr>
   <td class="control" colspan="2">
    <input class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
    <input type="button" id="blacklist_return" value="<?php echo _("Return to Rules List") ?>" />
   </td>
  </tr>
 </table>
</form>
