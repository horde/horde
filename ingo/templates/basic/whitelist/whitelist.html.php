<form method="post" name="filters" action="<?php echo $this->formurl ?>">
 <input type="hidden" name="actionID" value="rule_update" />

 <h1 class="header">
  <?php echo _("Whitelist") ?>
  <?php echo $this->hordeHelp('ingo', 'whitelist') ?>
<?php if ($this->disable): ?>
  [<span style="color:red"><?php echo _("Disabled") ?></span>]
<?php endif; ?>
 </h1>

 <table>
  <tr>
   <td colspan="2">
    <em><?php echo $this->hordeLabel('whitelist', _("Wh_itelist addresses:")) ?></em>
   </td>
  </tr>
  <tr>
   <td>
    <textarea name="whitelist" id="whitelist" rows="15" cols="80"><?php echo $this->h($this->whitelist) ?></textarea>
   </td>
   <td>
    <?php echo $this->hordeHelp('ingo', 'whitelist-addresses') ?>
   </td>
  </tr>
  <tr>
   <td colspan="2" class="control">
    <input class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
    <input type="button" id="whitelist_return" value="<?php echo _("Return to Rules List") ?>" />
   </td>
  </tr>
 </table>
</form>
