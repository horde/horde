<?php if ($pageOb['mode'] != 'list') { return; } ?>
<table width="100%" cellspacing="0">
 <tr class="mboxcontrol">
  <td>
   <div class="leftFloat">
    <form name="select<?php echo $form ?>" action="javascript:void(0)" onsubmit="return false">
    <select name="filter" onchange="makeSelection(<?php echo $form ?>);">
     <option value="" selected="selected"><?php echo _("Select") ?>:</option>
     <option value="!0"><?php echo _("All") ?></option>
     <option value="0"><?php echo _("None") ?></option>
     <option value="~0"><?php echo _("Invert") ?></option>
<?php $flagpos = 0; ?>
<?php foreach ($accounttypes as $typeId => $typeLabel): ?>
     <option value="<?php echo pow(2, $flagpos) ?>"><?php echo $typeLabel ?></option>
     <option value="!<?php echo pow(2, $flagpos++) ?>"><?php echo sprintf(_("Not %s"), $typeLabel) ?></option>
<?php endforeach; ?>
    </select>
    </form>
   </div>

   <div class="rightFloat">
<?php if ($pageOb['page_count'] > 1): ?>
    <form method="get" name="pagenav" action="<?php echo Horde::url('postings.php') ?>">
    <?php Horde_Util::pformInput() ?>
    <?php echo $pageOb['pages_first'] . '&nbsp;' . $pageOb['pages_prev'] ?>
    <input type="text" name="page" value="<?php echo htmlspecialchars($pageOb['page']) ?>" size="<?php echo Horde_String::length($pageOb['page_count']) ?>" />
    <?php echo $pageOb['pages_next'] . '&nbsp;' . $pageOb['pages_last'] ?>
    </form>
<?php endif; ?>
   </div>
  </td>
 </tr>
</table>
