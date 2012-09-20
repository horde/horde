<form name="fmanager" id="fmanager" method="post" enctype="multipart/form-data" action="<?php echo $this->folders_url ?>">
 <?php echo $this->hiddenFieldTag('actionID') ?>
 <?php echo $this->hiddenFieldTag('folders_token', $this->folders_token) ?>
 <?php echo $this->hiddenFieldTag('import_mbox', $this->import_mbox) ?>

 <div class="header">
  <?php echo _("Import Messages") ?>
 </div>

 <div class="item">
  <br />
  <label for="mbox_upload"><?php echo _("Import mbox or .eml file") ?></label>
  <input id="mbox_upload" name="mbox_upload" type="file" size="30" />
  <?php echo _("into mailbox") ?>
  <strong><?php echo $this->h($this->import_mbox) ?></strong>.
  <input id="btn_import" type="button" class="horde-default" value="<?php echo _("Import") ?>" />
  <input id="btn_return" type="button" class="horde-cancel" value="<?php echo _("Cancel") ?>" />
 </div>
</form>
