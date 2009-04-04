<table width="100%" cellspacing="0">
 <tr>
  <td class="mboxcontrol <?php if ($form == 1) { echo 'bottombordercollapse'; } else { echo 'topborder'; } ?>">
   <div class="rightFloat">
   </div>
   <div class="leftFloat">
<?php if ($pageOb['mode'] == 'list'): ?>
    <ul class="msgactions">
     <li><?php echo Horde::widget('#', _("Delete"), 'widget', '', "Submit('delete_postings'); return false;", _("_Delete")); ?></li>
     <li><?php echo Horde::widget('#', _("Edit"), 'widget', '', "Submit('edit_postings'); return false;", _("_Edit")); ?></li>
     <li><?php echo Horde::widget('#', _("Shift"), 'widget', '', "Submit('shift_postings'); return false;", _("S_hift")); ?></li>
     <li class="lastnavbar"><?php echo Horde::widget('#', _("Transfer"), 'widget', '', "Submit('transfer_postings'); return false;", _("Trans_fer")); ?></li>
    </ul>
<?php else: ?>
    <input type="submit" class="button" value="<?php echo _("Save") ?>" onclick="return Submit('<?php echo $actionID ?>');" />
<?php if (in_array($prefs->getValue('active_postingtype'), array(FIMA_POSTINGTYPE_FORECAST, FIMA_POSTINGTYPE_BUDGET))): ?>
    <input type="button" class="button" value="<?php echo _("Autofill") ?>" onclick="autofillPostings();" />
<?php endif; ?>
<?php endif; ?>
   </div>
  </td>
 </tr>
</table>
