 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo _("Shifting") ?>&nbsp;&nbsp;</strong>
  </td>
  <td width="100%"><?php echo sprintf(_("%d selected Postings"), count($postings)) ?>
<?php foreach($postings as $postingId => $posting): ?>
    <input name="posting_id[]" type="hidden" value="<?php echo $posting['posting_id'] ?>" />
<?php endforeach; ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('type', _("Posting T_ype")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildPostingTypeWidget('type', null, '', _("don't change"), false) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('asset', _("A_sset Account")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildAccountWidget('asset', null, '', _("don't change"), false, array(array('type', FIMA_ACCOUNTTYPE_ASSET))) ?>
  </td>
 </tr>
 <tr>
  <td class="rightAlign nowrap">
   <strong><?php echo Horde::label('account', _("Posting A_ccount")) ?></strong>&nbsp;
  </td>
  <td width="100%">
   <?php echo Fima::buildAccountWidget('account', null, '', _("don't change")) ?>
  </td>
 </tr>
