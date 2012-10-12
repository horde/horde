<form name="fmanager" method="post" action="<?php echo $this->folders_url ?>">
 <?php echo $this->hiddenFieldTag('actionID', $this->actionID) ?>
 <?php echo $this->hiddenFieldTag('folders_token', $this->folders_token) ?>

 <div class="header leftAlign">
  <?php echo _("Folder Actions - Confirmation") ?>
 </div>

 <div class="control leftAlign">
<?php if ($this->delete): ?>
  <?php echo _("You are attempting to delete the following mailbox(es).") ?>
<?php elseif ($this->empty): ?>
  <?php echo _("You are attempting to delete all messages contained in the following mailbox(es).") ?>
<?php endif; ?>
  <br />
  <?php echo _("If you continue, all messages in the mailbox(es) will be lost!") ?>
 </div>

<?php foreach ($this->mboxes as $v): ?>
 <div class="striped">
  <label><input type="checkbox" class="checkbox" name="mbox_list[]" value="<?php echo $v['val'] ?>" checked="checked" /> <?php echo $this->h($v['name']) ?> (<?php echo $v['msgs'] ?> <?php echo _("messages") ?>)</label>
 </div>
<?php endforeach; ?>

 <div class="control leftAlign">
  <?php echo $this->submitTag($this->delete ? _("Delete Selected Mailboxes") : ($this->empty ? _("Empty Selected Mailboxes") : ''), array('class' => 'horde-delete', 'name' => 'submit')) ?>
  <input id="btn_return" type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
 </div>
</form>
