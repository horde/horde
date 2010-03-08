<?php
/* Get the list of flags. */
$flaglist = $GLOBALS['injector']->getInstance('IMP_Imap_Flags')->getList(array('div' => true, 'fgcolor' => true));

/* Get the graphics. */
$picker_img = Horde::img('colorpicker.png', _("Color Picker"));

?>
<input type="hidden" name="flag_action" id="flag_action" />
<input type="hidden" name="flag_data" id="flag_data" />
<table class="flagmanagement">
 <thead>
  <tr>
   <td>Label</td>
   <td>Icon</td>
   <td class="colorheader">Color</td>
  </tr>
 </thead>
 <tbody>
<?php foreach ($flaglist as $key => $val):
    $hash = hash('md5', $key);
    $bgid = 'bg_' . $hash;
    $color = htmlspecialchars($val['b']);
    $fgcolor = htmlspecialchars($val['f']);
    $label = htmlspecialchars($val['l']);
    $bgstyle = 'background-color:' . $color;
?>
  <tr>
   <td>
<?php if ($val['t'] == 'imapp'): ?>
   <input name="<?php echo 'label_' . $hash ?>" value="<?php echo $label ?>" />
<?php else: ?>
    <?php echo Horde::label($bgid, $label) ?>
<?php endif; ?>
   </td>
   <td class="flagicon">
<?php if ($val['t'] == 'imapp'): ?>
    <div class="msgflags flagUser" style="<?php echo $bgstyle ?>"></div>
<?php else: ?>
    <?php echo $val['div'] ?>
<?php endif; ?>
   </td>
   <td>
    <input size="7" style="<?php echo $bgstyle ?>;color:<?php echo $fgcolor ?>" id="<?php echo $bgid ?>" name="<?php echo $bgid ?>" value="<?php echo $color ?>" />
    <a class="flagcolorpicker" href="#"><?php echo $picker_img ?></a>
<?php if (!empty($val['d'])): ?>
    <a class="flagdelete" href="#"><span class="deleteImg"></span></a>
<?php endif; ?>
   </td>
  </tr>
 </tbody>
<?php endforeach; ?>
</table>
<input id="new_button" type="button" class="button" value="<?php echo _("New Flag") ?>" />
