<?php echo $this->inputform; ?>
<br class="spacer" />

<?php if (!empty($this->fields)): ?>
<div class="header">
 <span class="rightFloat">
  <?php echo $this->actions; ?>
 </span>
 <?php echo _("Fields"); ?>
</div>

<table width="100%" class="linedRow leftAlign" cellspacing="0">
 <tr class="item">
  <th class="nowrap" width="1%">&nbsp;</th>
  <th>
   <?php echo $this->fieldproperties['name']; ?>
  </th>
  <th>
   <?php echo $this->fieldproperties['label']; ?>
  </th>
  <th>
   <?php echo $this->fieldproperties['type']; ?>
  </th>
  <th>
   <?php echo $this->fieldproperties['required']; ?>
  </th>
  <th>
   <?php echo $this->fieldproperties['readonly']; ?>
  </th>
 </tr>
 <?php foreach ($this->fields as $field): ?>
 <tr>
  <td class="nowrap">
   <a href="<?php echo $field['edit_url']; ?>"><?php echo $this->images['edit']; ?></a>
   <a href="<?php echo $field['del_url']; ?>"><?php echo $this->images['delete']; ?></a>
  </td>
  <td>
   <?php echo $field['name']; ?>
  </td>
  <td>
   <?php echo $field['label']; ?>
  </td>
  <td>
   <?php echo $field['type']; ?>
  </td>
  <td>
   <?php echo $field['required']; ?>
  </td>
  <td>
   <?php echo $field['readonly']; ?>
  </td>
 </tr>
 <?php endforeach; ?>
</table>
<?php endif; ?>
