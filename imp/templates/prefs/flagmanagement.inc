<?php
/* Add necessary javascript files. */
Horde::addScriptFile('prototype.js', 'horde', true);
Horde::addScriptFile('colorpicker.js', 'horde', true);
Horde::addScriptFile('flagmanagement.js', 'imp', true);

/* Get the list of flags. */
$imp_flags = &IMP_Imap_Flags::singleton();
$flaglist = $imp_flags->getList(array('div' => true, 'fgcolor' => true));

/* Get the graphics. */
$picker_img = Horde::img('colorpicker.png', _("Color Picker"), '', $registry->getImageDir('horde'));
$delete_img = Horde::img('delete.png');

IMP::addInlineScript(array(
    'ImpFlagmanagement.new_prompt = ' . Horde_Serialize::serialize(_("Please enter the label for the new flag:"), Horde_Serialize::JSON, NLS::getCharset())
));

?>
<input type="hidden" name="flag_action" id="flag_action" />
<input type="hidden" name="flag_data" id="flag_data" />
<input id="new_button" type="button" class="button" value="<?php echo _("New Flag") ?>" />
<table>
<?php foreach ($flaglist as $key => $val):
    $hash = hash('md5', $key);
    $bgid = 'bg_' . $hash;
    $color = htmlspecialchars($val['b']);
    $fgcolor = htmlspecialchars($val['f']);
    $label = htmlspecialchars($val['l']);
    $bgstyle = 'background-color:' . $color . ';color:' . $fgcolor;
?>
 <tr>
<?php if ($val['t'] == 'imapp'): ?>
  <td><input style="<?php echo $bgstyle ?>" name="<?php echo 'label_' . $hash ?>" value="<?php echo $label ?>" /></td>
<?php else: ?>
   <td style="<?php echo $bgstyle ?>"><strong><?php echo Horde::label($bgid, $label) ?></strong></td>
<?php endif; ?>
  <td>
<?php if (isset($val['div'])) { echo $val['div']; } ?>
  </td>
  <td>
   <input size="7" style="background:<?php echo $color ?>;color:<?php echo $fgcolor ?>" id="<?php echo $bgid ?>" name="<?php echo $bgid ?>" value="<?php echo $color ?>" />
   <a class="flagcolorpicker" href="#"><?php echo $picker_img ?></a>
<?php if (!empty($val['d'])): ?>
   <a class="flagdelete" href="#"><?php echo $delete_img ?></a>
<?php endif; ?>
  </td>
 </tr>
<?php endforeach; ?>
</table>
