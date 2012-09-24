 <tr>
  <td>
<?php
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    $label = sprintf(_("Edit \"%s\""), $memo['desc']);
    echo Horde::url($memourl)->add('actionID', 'modify_memo')->link(array('title' => $label))
        . Horde::img('edit.png', $label, '') . '</a>';
}
?>
  </td>
<?php if ($showNotepad): ?>
  <td><?php echo htmlspecialchars($notepad) ?></td>
<?php endif; ?>
  <td>
   <?php echo Horde::linkTooltip(Horde::url($viewurl), '', '', '', '', ($memo['body'] != $memo['desc']) ? Mnemo::getNotePreview($memo) : '') . (strlen($memo['desc']) ? htmlspecialchars($memo['desc']) : '<em>' . _("Empty Note") . '</em>') ?></a>
  </td>
  <td>
   <?php echo strftime($prefs->getValue('date_format'), $modDate) ?>
  </td>
  <td class="base-category" style="<?php echo Mnemo::getCssStyle($memo['category']) ?>"><?php echo htmlspecialchars($memo['category'] ? $memo['category'] : _("Unfiled")) ?></td>
 </tr>
