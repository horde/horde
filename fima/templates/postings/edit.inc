<?php
/* Cache created widgets. */
$assetWidgets = array(-1 => Fima::buildAccountWidget('asset[]', '', 'onfocus="updateAccount(this);" onblur="updateAssetResult(this);"', '', false, array(array('type', FIMA_ACCOUNTTYPE_ASSET)), true));
$accountWidgets = array(-1 => Fima::buildAccountWidget('account[]', '', 'onblur="updateEo(this);"', '', false, array(), true));
?>
<?php foreach($postings as $postingId => $posting): ?>
<?php $rowId++; ?>
 <tr id="row<?php echo $rowId ?>" class="posting">
  <td>
   <input name="posting_id[]" type="hidden" value="<?php echo $posting['posting_id'] ?>" />
   <input name="date[]" id="date<?php echo $rowId ?>" type="text" value="<?php echo $posting['date'] == '' ? '' : strftime($datefmt, $posting['date']) ?>" onfocus="updateDate(this);" onblur="formatDate(this);" size="10" maxlength="10" />
  </td>
  <td class="ohide">
<?php
echo $overflow_begin;
$a = ($posting['asset'] == '') ? -1 : $posting['asset'];
if (!isset($assetWidgets[$a])) { 
    $assetWidgets[$a] = Fima::buildAccountWidget('asset[]', $posting['asset'], 'onblur="updateAssetResult(this);"', false, false, array(array('type', FIMA_ACCOUNTTYPE_ASSET)), true);
}
echo $assetWidgets[$a];
echo $overflow_end;
?>
  <td class="ohide">
<?php
echo $overflow_begin;
$a = ($posting['account'] == '') ? -1 : $posting['account'];
if (!isset($accountWidgets[$a])) {
    $accountWidgets[$a] = Fima::buildAccountWidget('account[]', $posting['account'], 'onblur="updateEo(this);"', false, false, array(), true);
}
echo $accountWidgets[$a];
echo $overflow_end;
?>
  </td>   
  <td class="ohide">
   <?php echo $overflow_begin ?><input name="desc[]" type="text" value="<?php echo $posting['desc'] ?>" size="50" maxlength="250" /><?php echo $overflow_end ?>
  </td>
  <td>
   <input name="amount[]" type="text" value="<?php echo $posting['posting_id'] === null ? '' : Fima::convertValueToAmount($posting['amount']) ?>" onfocus="updateAssetResult(this);" onblur="formatAmount(this); updateResult(); updateAssetResult(this);" size="12" maxlength="255" class="amount" />
  </td>
  <td align="center">
   <input name="eo[<?php echo $rowId-1 ?>]" type="hidden" value="0" />
   <input name="eo[<?php echo $rowId-1 ?>]" id="eo<?php echo $rowId-1 ?>" type="checkbox" value="1" <?php if ($posting['eo']) { echo ' checked="checked"'; } ?> />
  </td>
 </tr>
<?php endforeach; ?>
 <tr class="postingInfo">
  <td colspan="4" class="rightAlign"><span id="infoasset"></span> <?php echo _("Result") ?>:</td>
  <td><span id="infoassetresult" class="positive postingResult"></span></div>
  <td></td>
 </tr>
 <tr class="postingInfo">
  <td colspan="4" class="rightAlign"><?php echo _("Current Result") ?>:</td>
  <td><span id="inforesult" class="positive postingResult"></span></div>
  <td></td>
 </tr>
