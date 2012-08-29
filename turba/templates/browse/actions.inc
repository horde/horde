<div class="horde-buttonbar">
  <form style="display:inline" name="f<?php echo $id = md5(mt_rand()) ?>" action="javascript:void(0);" onsubmit="return false;">
  <ul>
<?php if ($hasDelete): ?>
    <li><?php echo Horde::widget('#', $this->getType() == 'list' ? _("Remove from this list") : _("Delete"), '', '', "Submit('delete'); return false;", $this->getType() == 'list' ? _("_Remove from this list") : _("_Delete")) ?></li>
<?php endif ?>
<?php if ($hasEdit): ?>
    <li><?php echo Horde::widget('#', _("Edit"), 'widget', '', "document.contacts.action='" . Horde::url('edit.php') . "'; Submit('groupedit'); return false;", _("Edit")) ?></li>
<?php endif ?>
<?php if ($hasExport): ?>
    <li><?php echo Horde::widget('#', _("Export"), 'widget', '', "document.contacts.action='" . $GLOBALS['registry']->downloadUrl('', array('actionID' => 'export')) . "'; Submit('select'); return false;", _("Export")) ?></li>
<?php endif ?>
<?php if (!empty($copymove_source_options)): ?>
<?php if ($hasDelete): ?>
    <li><?php echo Horde::link('#', _("Move"), 'widget', '', "CopyMove('move', document.f" . $id . '.c' . $id . "); return false;") . _("Move") . '</a>' ?></li>
<?php endif ?>
    <li><?php echo Horde::link('#', _("Copy"), 'widget', '', "CopyMove('copy', document.f" . $id . '.c' . $id . "); return false;") . _("Copy") . '</a>' ?></li>
    <li>
      <label for="c<?php echo $id ?>" class="hidden"><?php echo _("Target Address Book") ?></label>
      <select name="c<?php echo $id ?>" id="c<?php echo $id ?>">
        <option value=""><?php echo _("to a different Address Book") ?></option>
        <?php echo $copymove_source_options ?>
      </select>
    </li>
<?php endif ?>
<?php if ($addToList): ?>
    <li><?php echo Horde::link('#', _("Add to"), 'widget', '', "Add(document.f" . $id . '.s' . $id . "); return false;") . _("Add") . '</a>' ?></li>
    <li>
      <label for="s<?php echo $id ?>" class="hidden"><?php echo _("Target Contact List") ?></label>
      <select name="s<?php echo $id ?>" id="s<?php echo $id ?>">
        <option value=""><?php echo _("to a Contact List") ?></option>
        <option value="" disabled="disabled">- - - - - - - - -</option>
<?php foreach ($addToList as $list): ?>
<?php if (is_array($list)): ?>
        <option value="<?php echo $list['source'] . ($list['key'] ? ':' . $list['key'] : '') ?>"><?php echo $list['name'] ?></option>
<?php else: ?>
        <option value="" disabled="disabled"><?php echo $list ?></option>
<?php endif; endforeach; ?>
      </select>
    </li>
<?php endif; ?>
<?php
$browse_link = Horde::url('browse.php');
if ($this->getType() == 'directory' &&
    !empty($cfgSources[Turba::$source]['map']['__type'])):
?>
    <li><?php echo Horde::widget($browse_link->copy()->add('show', 'lists'), _("Show Lists"), 'widget', '', '', _("Show _Lists")) ?></li>
    <li><?php echo Horde::widget($browse_link->copy()->add('show', 'contacts'), _("Show Contacts"), 'widget', '', '', _("Show _Contacts")) ?></li>
    <li><?php echo Horde::widget($browse_link->copy()->add('show', 'all'), _("Show Both"), 'widget', '', '', _("Show Both")) ?></li>
<?php endif ?>
  </ul>
  </form>
</div>
