<form method="post" name="filters" action="<?php echo $this->formurl ?>">
 <input type="hidden" name="actionID" value="rule_update" />

 <h1 class="header">
  <?php echo _("Whitelist") ?>
  <?php echo $this->hordeHelp('ingo', 'whitelist') ?>
<?php if ($this->disable): ?>
  [<span style="color:red"><?php echo _("Disabled") ?></span>]
<?php endif; ?>
 </h1>

 <div>
  <em><?php echo $this->hordeLabel('whitelist', _("Wh_itelist addresses:")) ?></em>
 </div>

 <div>
  <textarea name="whitelist" id="whitelist" rows="15" cols="80"><?php echo $this->h($this->whitelist) ?></textarea>
 </div>

 <div class="control">
  <input class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
  <input type="button" id="whitelist_return" value="<?php echo _("Return to Rules List") ?>" />
 </div>
</form>
