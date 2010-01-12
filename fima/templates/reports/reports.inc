<form method="post" name="report" action="report.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="open_report" />
<input type="hidden" name="sortby" value="<?php echo $params['sortby'] ?>" />
<input type="hidden" name="sortdir" value="<?php echo $params['sortdir'] ?>" />

<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>

<table cellspacing="0" width="100%" class="linedRow">
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('report_id', _("Repor_t")) ?></strong>
  </td>
  <td width="100%" colspan="4">
   <select name="report_id" id="report_id">
<?php foreach($_reports as $reportId => $reportLabel): ?>
    <option value="<?php echo $reportId ?>" <?php if ($reportId == $params['report_id']) { echo 'selected="selected"'; } ?>><?php echo $reportLabel; ?></option>
<?php endforeach; ?>
   </select>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('type', _("_Display")) ?></strong>
  </td>
  <td>
   <select name="display" id="display">
<?php foreach($displays as $displayId => $displayLabel): ?>
    <option value="<?php echo $displayId ?>" <?php if ($displayId == $params['display']) { echo 'selected="selected"'; } ?>><?php echo $displayLabel; ?></option>
<?php endforeach; ?>
   </select>
  </td>
  <td class="rightAlign nowrap" rowspan="4" valign="top">
   <strong><?php echo Horde::label('posting_account[]', _("Accounts")) ?></strong>  
  </td>
  <td class="nowrap" rowspan="4">
   <?php echo Fima::buildAccountWidget('posting_account[]', $params['posting_account'], '', false, true, array(array('type', FIMA_ACCOUNTTYPE_ASSET, '<>'))) ?>
  </td>
  <td width="100%" rowspan="4" valign="top">
<?php if($browser->hasFeature('javascript')): ?>
<?php
$types = Fima::getAccountTypes();
foreach($types as $typeId => $typeLabel):
    if ($typeId == FIMA_ACCOUNTTYPE_ASSET) {
        continue;
    }
?>
   <input name="account_type_<?php echo $typeId ?>" id="account_type_<?php echo $typeId ?>" onclick="selectAccounts('posting_account[]', '<?php echo $typeId ?>', this.checked)" type="checkbox" value="1" /> <?php echo Horde::img($typeId . '.png', $typeLabel) ?> <?php echo $typeLabel ?><br />
<?php endforeach; ?>
<?php endif; ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
    <strong><?php echo Horde::label('period_start', _("P_eriod")) ?></strong>
  </td>
  <td class="nowrap">
   <?php echo Fima::buildDateWidget('period_start', $params['period_start'], null, false, true) ?>
   &nbsp; - &nbsp
   <?php echo Fima::buildDateWidget('period_end', $params['period_end'], null, false, true) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
    <strong><?php echo Horde::label('reference_start', _("Re_ference")) ?></strong>
  </td>
  <td class="nowrap">
   <?php echo Fima::buildDateWidget('reference_start', $params['reference_start'], null, false, true) ?>
   &nbsp; - &nbsp
   <?php echo Fima::buildDateWidget('reference_end', $params['reference_end'], null, false, true) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
    <strong><?php echo _("Other") ?></strong>
  </td>
  <td class="nowrap">
   <input name="cumulate" type="hidden" value="0" />
   <input name="cumulate" type="checkbox" value="1" id="cumulate" <?php if ($params['cumulate']) { echo 'checked="checked"'; } ?>/>
   <?php echo Horde::label('cumulate', _("C_umulate")) ?>
   &nbsp;
   <input name="subaccounts" type="hidden" value="0" />
   <input name="subaccounts" type="checkbox" value="1" id="subaccounts" <?php if ($params['subaccounts']) { echo 'checked="checked"'; } ?>/>
   <?php echo Horde::label('subaccounts', _("_Subaccounts")) ?>
   &nbsp;
   <input name="nullrows" type="hidden" value="0" />
   <input name="nullrows" type="checkbox" value="1" id="nullrows" <?php if ($params['nullrows']) { echo 'checked="checked"'; } ?>/>
   <?php echo Horde::label('nullrows', _("_Null Rows")) ?>
   &nbsp;
   <input name="yearly" type="hidden" value="0" />
   <input name="yearly" type="checkbox" value="1" id="yearly" <?php if ($params['yearly']) { echo 'checked="checked"'; } ?>/>
   <?php echo Horde::label('yearly', _("_Yearly")) ?>
<?php if ($graphs): ?>
   &nbsp;
   <input name="graph" type="hidden" value="0" />
   <input name="graph" type="checkbox" value="1" id="graph" <?php if ($params['graph']) { echo 'checked="checked"'; } ?>/>
   <?php echo Horde::label('graph', _("_Chart")) ?>
<?php endif; ?>
  </td>
 </tr>
</table>
<br />

<input type="submit" class="button" value="<?php echo _("Open Report") ?>" />
<input type="submit" class="button" value="<?php echo _("Reset") ?>" onclick="document.report.actionID.value='clear_report';" />

</form>
