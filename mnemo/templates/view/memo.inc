<?php
$memourl = Horde::url('memo.php')->add(array('memo' => $memo_id,
                                             'memolist' => $memolist_id));

$share = $GLOBALS['mnemo_shares']->getShare($memolist_id);
?>
<div class="header">
 <div class="rightFloat">
<?php
echo Horde::widget(array('url' => Horde::url('note/pdf.php')->add(array('note' => $memo_id, 'notepad' => $memolist_id)), 'title' => _("Save as PDF"), 'class' => 'smallheader'));
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
    echo ' | ' . Horde::widget(array('url' => $memourl->add('actionID', 'modify_memo'), 'class' => 'smallheader', 'title' => _("_Edit")));
}
if ($share->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE)) {
    echo ' | ' . Horde::widget(array('url' => $memourl->add('actionID', 'delete_memos'), 'class' => 'smallheader', 'onclick' => $prefs->getValue('delete_opt') ? 'return window.confirm(\'' . addslashes(_("Really delete this note?")) . '\');' : '', 'title' => _("_Delete")));
}
?>
 </div>
 <?php echo htmlspecialchars($memo['desc']) ?>
</div>

<table cellspacing="0" width="100%" class="headerbox striped nowrap">
<tr>
  <td class="rightAlign"><strong><?php echo _("Category") ?></strong>&nbsp;</td>
  <td width="100%"><?php echo htmlspecialchars($memo['category'] ? $memo['category'] : _("Unfiled")) ?></td>
</tr>

<?php if (isset($memo['created'])): ?>
<tr>
  <td class="rightAlign"><strong><?php echo _("Created") ?></strong>&nbsp;</td>
  <td>
    <?php echo $memo['created']->strftime($prefs->getValue('date_format')) . ' ' . $memo['created']->format($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a') ?>
<?php if (isset($memo['createdby'])): ?>
    <?php echo htmlspecialchars($memo['createdby']) ?>
<?php endif ?>
  </td>
</tr>
<?php endif ?>

<?php if (isset($memo['modified'])): ?>
<tr>
  <td class="rightAlign"><strong><?php echo _("Last Modified") ?></strong>&nbsp;</td>
  <td>
    <?php echo $memo['modified']->strftime($prefs->getValue('date_format')) . ' ' . $memo['modified']->format($prefs->getValue('twentyFour') ? 'G:i' : 'g:i a') ?>
<?php if (isset($memo['modifiedby'])): ?>
    <?php echo htmlspecialchars($memo['modifiedby']) ?>
<?php endif ?>
</td>
</tr>
<?php endif ?>

 <tr>
  <td colspan="2">
<?php if ($show_passphrase): ?>
   <div class="notePassphrase">
    <form action="view.php" name="passphrase" method="post">
     <?php echo Horde_Util::formInput() ?>
     <input type="hidden" name="memolist" value="<?php echo $memolist_id ?>" />
     <input type="hidden" name="memo" value="<?php echo $memo_id ?>" />
     <?php echo Horde::label('memo_passphrase', _("_Password")) ?>:
     <input type="password" id="memo_passphrase" name="memo_passphrase" />
     <input type="submit" class="horde-default" value="<?php echo _("Decrypt") ?>" />
    </form>
   </div>
<?php else: ?>
   <div class="noteBody">
    <?php $body = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($memo['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO)); try { $body = Horde::callHook('format_description', array($body), 'mnemo', $body); } catch (Horde_Exception_HookNotSet $e) {} echo $body; ?>
   </div>
<?php endif; ?>
  </td>
 </tr>
</table>
<?php if ($show_passphrase): ?>
<script type="text/javascript" >
document.passphrase.memo_passphrase.focus();
</script>
<?php endif; ?>
