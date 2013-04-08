<input name="cAction" value="" type="hidden" />
<input name="category" value="" type="hidden" />
<input type="button" class="horde-create" id="add_category" value="<?php echo _("New Category") ?>" />

<table>
 <tr>
  <td style="background-color:<?php echo $this->h($this->default_color) ?>;color:<?php echo $this->h($this->default_fgcolor) ?>">
   <strong><?php echo $this->hordeLabel($this->default_id, _("Default Color")) ?></strong>
  </td>
  <td>
<?php if ($this->picker_img): ?>
   <input size="7" style="background:<?php echo $this->h($this->default_color) ?>;color:<?php echo $this->h($this->default_fgcolor) ?>" id="<?php echo $this->default_id ?>" name="<?php echo $this->default_id ?>" value="<?php echo $this->h($this->default_color) ?>" />
   <a href="#" class="categoryColorPicker"><?php echo $this->hordeImage('colorpicker.png', _("Color Picker")) ?></a>
<?php endif; ?>
  </td>
 </tr>
 <tr>
  <td style="background-color:<?php echo $this->h($this->unfiled_color) ?>;color:<?php echo $this->h($this->unfiled_fgcolor) ?>">
   <strong><?php echo $this->hordeLabel($this->unfiled_id, _("Unfiled")) ?></strong>
  </td>
  <td>
<?php if ($this->picker_img): ?>
   <input size="7" style="background:<?php echo $this->h($this->unfiled_color) ?>;color:<?php echo $this->h($this->unfiled_fgcolor) ?>" id="<?php echo $this->unfiled_id ?>" name="<?php echo $this->unfiled_id ?>" value="<?php echo $this->h($this->unfiled_color) ?>" />
   <a href="#" class="categoryColorPicker"><?php echo $this->hordeImage('colorpicker.png', _("Color Picker")) ?></a>
<?php endif; ?>
  </td>
 </tr>
<?php foreach ($this->categories as $c): ?>
 <tr>
  <td style="background-color:<?php echo $this->h($c['color']) ?>;color:<?php echo $this->h($c['fgcolor']) ?>">
   <strong><?php echo $this->hordeLabel($c['id'], $c['name'] == '_default_' ? _("Default Color") : $this->h($c['name'])) ?></strong>
  </td>
  <td>
<?php if ($this->picker_img): ?>
   <input size="7" style="background:<?php echo $this->h($c['color']) ?>;color:<?php echo $this->h($c['fgcolor']) ?>" id="<?php echo $c['id'] ?>" name="<?php echo $c['id'] ?>" value="<?php echo $this->h($c['color']) ?>" />
   <a href="#" class="categoryColorPicker"><?php echo $this->hordeImage('colorpicker.png', _("Color Picker")) ?></a>
<?php endif; ?>
   <a href="#" class="categoryDelete" category="<?php echo $this->h($c['name']) ?>"><?php echo $this->hordeImage('delete.png') ?></a>
  </td>
 </tr>
<?php endforeach; ?>
</table>
