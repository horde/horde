<form method="post" name="filters" action="<?php echo Horde::url('blacklist.php') ?>">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="rule_update" />

<h1 class="header">
 <?php echo _("Blacklist") ?>
 <?php echo Horde_Help::link('ingo', 'blacklist') ?>
<?php if (!empty($bl_rule['disable'])): ?>
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
      <input type="radio" name="action" id="action_delete" value="delete" <?php if (empty($blacklist_folder)) echo 'checked="checked" ' ?>/> <?php echo Horde::label('action_delete', _("_Delete message completely")) ?><br />
<?php if ($flagonly): ?>
      <input type="radio" name="action" id="action_mark" value="mark" <?php if ($blacklist_folder == Ingo::BLACKLIST_MARKER) echo 'checked="checked" ' ?>/> <?php echo Horde::label('action_mark', _("Mar_k message as deleted")) ?><br />
<?php endif; ?>
      <input type="radio" name="action" id="action_folder" value="folder" <?php if (!empty($blacklist_folder) && ($blacklist_folder != Ingo::BLACKLIST_MARKER)) echo 'checked="checked" ' ?>/> <?php echo Horde::label('action_folder', _("_Move message to folder:")) ?>
      <label for="actionvalue" class="hidden"><?php echo _("Select target folder") ?></label>
      <?php echo $folder_list ?>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'blacklist-action') ?>
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <em><?php echo Horde::label('blacklist', _("_Enter each address on a new line:")) ?></em>
    </td>
  </tr>
  <tr>
    <td>
      <textarea name="blacklist" id="blacklist" rows="15" cols="80"><?php echo htmlspecialchars(implode("\n", $blacklist->getBlacklist())) ?></textarea>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'blacklist-addresses') ?>
    </td>
  </tr>
  <tr>
    <td class="control" colspan="2">
      <input class="horde-default" type="submit" value="<?php echo _("Save") ?>" />
      <input type="button" onclick="document.location.href='<?php echo Horde::url('filters.php', true) ?>'" value="<?php echo _("Return to Rules List") ?>" />
    </td>
  </tr>
</table>
</form>
