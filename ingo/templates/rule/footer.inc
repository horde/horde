      </table>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'rule-matches') ?>
    </td>
  </tr>

  <tr>
    <td>
      <em><?php echo Horde::label('action', _("Do this:")) ?></em>
      <br />
      <select onchange="document.rule.submit(); return true;" id="action" name="action">
        <?php echo $actionselect ?>
      </select>
      <?php echo $actionvaluelabel ?>
      <?php echo $actionvalue ?>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'rule-action') ?>
    </td>
  </tr>

<?php if ($current_action->flags && $ingo_script->imapFlags()): ?>
  <tr>
    <td>
      <em><?php echo _("Mark message as:") ?></em>
      <br />
      <table>
        <tr>
          <td>
            <input type="checkbox" id="seen" name="flags[]" value="<?php echo Ingo_Storage::FLAG_SEEN ?>"<?php echo (Ingo_Storage::FLAG_SEEN & $rule['flags']) ? ' checked="checked" ' : '' ?>/><?php echo Horde::label('seen', _("Seen")) ?>
          </td>
          <td>
            <input type="checkbox" id="flagged" name="flags[]" value="<?php echo Ingo_Storage::FLAG_FLAGGED ?>"<?php echo (Ingo_Storage::FLAG_FLAGGED & $rule['flags']) ? ' checked="checked" ' : '' ?>/><?php echo Horde::label('flagged', _("Flagged For Followup")) ?>
          </td>
          <td>
            <input type="checkbox" id="answered" name="flags[]" value="<?php echo Ingo_Storage::FLAG_ANSWERED ?>"<?php echo (Ingo_Storage::FLAG_ANSWERED & $rule['flags']) ? ' checked="checked" ' : '' ?>/><?php echo Horde::label('answered', _("Answered")) ?>
          </td>
          <td>
            <input type="checkbox" id="deleted" name="flags[]" value="<?php echo Ingo_Storage::FLAG_DELETED ?>"<?php echo (Ingo_Storage::FLAG_DELETED & $rule['flags']) ? ' checked="checked" ' : '' ?>/><?php echo Horde::label('deleted', _("Deleted")) ?>
          </td>
        </tr>
      </table>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'rule-mark') ?>
    </td>
  </tr>
<?php endif; ?>

<?php if ($ingo_script->stopScript()): ?>
  <tr>
    <td>
      <input type="checkbox" id="stop" name="stop" value="1"<?php echo ($rule['stop']) ? ' checked="checked"' : '' ?> /> <?php echo Horde::label('stop', _("Stop checking if this rule matches?")) ?>
    </td>
    <td>
      <?php echo Horde_Help::link('ingo', 'rule-stop') ?>
    </td>
  </tr>
<?php endif; ?>

  <tr>
    <td class="horde-form-buttons" colspan="2">
      <input class="horde-default" type="button" onclick="document.rule.actionID.value='rule_save';document.rule.submit(); return true;" value="<?php echo _("Save") ?>" />
      <input type="button" onclick="document.location.href='<?php echo Horde::url('filters.php', true) ?>'" value="<?php echo _("Return to Filters List") ?>" />
    </td>
  </tr>
</table>
</form>
