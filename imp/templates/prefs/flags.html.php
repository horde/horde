<input type="hidden" name="flag_action" id="flag_action" />
<input type="hidden" name="flag_data" id="flag_data" />
<table class="flagmanagement">
 <thead>
  <tr>
   <td><?php echo _("Label") ?></td>
   <td><?php echo _("Icon") ?></td>
   <td class="colorheader"><?php echo _("Color") ?></td>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->flags as $v): ?>
  <tr>
   <td>
<?php if (isset($v['user'])): ?>
<?php if ($this->locked): ?>
    <?php echo $v['label'] ?>
<?php else: ?>
    <input name="<?php echo $v['label_name'] ?>" value="<?php echo $v['label'] ?>" />
<?php endif; ?>
<?php else: ?>
    <?php echo $v['label'] ?>
<?php endif; ?>
   </td>
   <td class="flagicon">
<?php if (!isset($v['user'])): ?>
    <?php echo $v['icon'] ?>
<?php endif; ?>
   </td>
   <td>
<?php if (!$this->locked): ?>
    <?php echo $this->textFieldTag($v['colorid'], $v['color'], array('size' => 5, 'style' => $v['colorstyle'])) ?>
    <a class="flagcolorpicker" href="#"><?php echo $this->picker_img ?></a>
<?php if (isset($v['user'])): ?>
    <a class="flagdelete" href="#"><span class="iconImg deleteImg"></span></a>
<?php endif; ?>
<?php endif; ?>
   </td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>

<?php if (!$this->locked): ?>
<div>
 <input id="new_button" type="button" class="horde-create" value="<?php echo _("New Flag") ?>" />
</div>
<?php endif; ?>
