<form method="post" name="filters" action="<?php echo Horde::url('whitelist.php') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="rule_update" />

<h1 class="header">
  <?php echo _("Whitelist") ?>
  <?php echo Horde_Help::link('ingo', 'whitelist') ?>
<?php if (!empty($wl_rule['disable'])): ?>
  [<span style="color:red"><?php echo _("Disabled") ?></span>]
<?php endif; ?>
</h1>

<table>
  <tr>
    <td colspan="2">
      <em><?php echo Horde::label('whitelist', _("Wh_itelist addresses:")) ?></em>
    </td>
  </tr>
  <tr>
    <td>
      <textarea name="whitelist" id="whitelist" rows="15" cols="80"><?php echo htmlspecialchars(implode("\n", $whitelist->getWhitelist())) ?></textarea>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'whitelist-addresses') ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="control">
      <input class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
      <input type="button" onclick="document.location.href='<?php echo Horde::url('filters.php', true) ?>'" value="<?php echo _("Return to Rules List") ?>" />
    </td>
  </tr>
</table>
</form>
