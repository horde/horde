 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('type_from', _("Transfer from")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildPostingTypeWidget('type_from', $prefs->getValue('active_postingtype'), ''); ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('period_from', _("Period from")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildDateWidget('period_from', 0, null, _("All"), true) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('keep', _("_Keep original Postings")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <input name="keep" type="hidden" value="0" />
   <input name="keep" type="checkbox" value="1" id="keep" checked="checked" />
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap" valign="top">
   <strong><?php echo Horde::label('summarize', _("_Summarize Accounts")) ?></strong>&nbsp;
  </td>
  <td valign="top" width="100%">
   <input id="summarize_none" name="summarize" type="radio" value="none" checked="checked" />
   <?php echo Horde::label('summarize_none', _("Don't summarize.")) ?><br />
   <input id="summarize_combine" name="summarize" type="radio" value="combine" />
   <?php echo Horde::label('summarize_combine', _("Summarize by combining.")) ?><br />
   <input id="summarize_post" name="summarize" type="radio" value="post" />
   <?php echo Horde::label('summarize_post', _("Summarize by posting against")) ?><br />
   <?php echo Fima::buildAccountWidget('summarize_post_account', null, 'onchange="document.postings.summarize[2].checked = true;"', false, false, array(array('type', FIMA_ACCOUNTTYPE_ASSET))) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('type_to', _("Transfer to")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildPostingTypeWidget('type_to', null); ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('period_to', _("Period to")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildDateWidget('period_to', 0, null, _("All"), true) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('delete', _("_Delete existing Postings")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <input name="delete" type="hidden" value="0" />
   <input name="delete" type="checkbox" value="1" />
  </td>
 </tr>
