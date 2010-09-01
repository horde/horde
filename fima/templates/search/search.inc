<form method="post" name="search" action="postings.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="search_postings" />

<div class="header">
<?php echo htmlspecialchars($title) ?>
<?php if (isset($_SESSION['fima_search'])): ?>
<?php echo Horde::link(Horde_Util::addParameter(Horde::url('search.php'), 'actionID', 'clear_search'), _("Clear Search Query")) . Horde::img('delete.png', _("Clear Search Query")) ?></a>
<?php endif; ?>
</div>

<table cellspacing="0" width="100%" class="linedRow">
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('search_type', _("Search Posting T_ype")) ?></strong>&nbsp;
  </td>
  <td width="100%" colspan="2">
   <select name="search_type" id="search_type">
<?php foreach($types as $typeId => $typeLabel): ?>
    <option value="<?php echo $typeId ?>" <?php if ($typeId == $search['type']) { echo 'selected="selected"'; } ?>><?php echo $typeLabel; ?></option>
<?php endforeach; ?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap" valign="top">
    <strong><?php echo Horde::label('search_date_start', _("Search _Date Start")) ?></strong>&nbsp;
  </td>
  <td class="nowrap" width="20%">
   <?php echo Fima::buildDateWidget('search_date_start', $search['date_start']) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap" valign="top">
    <strong><?php echo Horde::label('search_date_end', _("Search Dat_e End")) ?></strong>&nbsp;
  </td>
  <td class="nowrap" width="20%">
   <?php echo Fima::buildDateWidget('search_date_end', $search['date_end']) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap" valign="top">
    <strong><?php echo Horde::label('search_asset[]', _("Search A_sset Accounts")) ?></strong>&nbsp;
  </td>
  <td width="100%" colspan="2" valign="top">
   <?php echo Fima::buildAccountWidget('search_asset[]', $search['asset'], '', false, true, array(array('type', FIMA_ACCOUNTTYPE_ASSET))) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap" valign="top">
    <strong><?php echo Horde::label('search_account[]', _("Search Posting A_ccounts")) ?></strong>&nbsp;
  </td>
  <td width="20%" valign="top">
   <?php echo Fima::buildAccountWidget('search_account[]', $search['account'], '', false, true) ?>
  </td>
  <td valign="top">
<?php if($browser->hasFeature('javascript')): ?>
<?php
$types = Fima::getAccountTypes();
foreach($types as $typeId => $typeLabel):
?>
   <input name="account_type_<?php echo $typeId ?>" id="account_type_<?php echo $typeId ?>" onclick="selectAccounts('search_account[]', '<?php echo $typeId ?>', this.checked)" type="checkbox" value="1" /> <?php echo Horde::img($typeId . '.png', $typeLabel) ?> <?php echo $typeLabel ?><br />
<?php endforeach; ?>
<?php endif; ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('search_desc', _("Search Descriptio_n")) ?></strong>&nbsp;
  </td>
  <td width="100%" colspan="2">
   <input name="search_desc" id="search_desc" type="text" value="<?php echo $search['desc'] ?>" size="50" maxlength="250" />
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('search_amount_start', _("Search Amo_unt Start")) ?></strong>&nbsp;
  </td>
  <td width="100%" colspan="2">
   <input name="search_amount_start" id="search_amount_start" type="text" value="<?php echo $search['amount_start'] ?>" onblur="formatAmount(this);" size="15" maxlength="15" class="amount" />
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('search_amount_end', _("Search Amoun_t End")) ?></strong>&nbsp;
  </td>
  <td width="100%" colspan="2">
   <input name="search_amount_end" id="search_amount_end" type="text" value="<?php echo $search['amount_end'] ?>" onblur="formatAmount(this);" size="15" maxlength="15" class="amount" />
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('search_eo', _("Search e.o. Postin_gs")) ?></strong>&nbsp;
  </td>
  <td width="100%" colspan="2">
   <select name="search_eo" id="search_eo">
<?php foreach($eos as $eoId => $eoLabel): ?>
    <option value="<?php echo $eoId ?>" <?php if ($eoId == $search['eo']) { echo 'selected="selected"'; } ?>><?php echo $eoLabel; ?></option>
<?php endforeach; ?>
   </select>
  </td>
 </tr>
</table>
<br />

<input type="submit" class="button" value="<?php echo _("Search") ?>" />
<input type="reset" class="button" value="<?php echo _("Clear Form") ?>" />

</form>
