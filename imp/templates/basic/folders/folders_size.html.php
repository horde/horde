<div class="header leftAlign">
 <?php echo _("Mailbox Sizes") ?>
</div>

<br />
<table class="horde-table sortable">
 <thead>
  <tr>
   <th><?php echo _("Mailbox") ?></th>
   <th><?php echo _("Size") ?></th>
  </tr>
 </thead>

 <tbody>
<?php foreach ($this->mboxes as $v): ?>
  <tr>
   <td>
    <?php echo $this->escape($v['name']) ?>
   </td>
   <td class="rightAlign" sortval="<?php echo $v['sort'] ?>">
    <?php echo $v['size'] ?>
   </td>
  </tr>
<?php endforeach; ?>
 </tbody>

 <tfoot>
  <tr>
   <td>
    <strong><?php echo _("Sum") ?></strong>
   </td>
   <td class="rightAlign">
    <strong><?php echo $this->mboxes_sum ?></strong>
   </td>
  </tr>
 </tfoot>
</table>

<form name="returnform">
 <div class="horde-form-buttons">
  <input id="btn_return" type="button" class="horde-default" value="<?php echo _("Return to Folders View") ?>" />
 </div>
</form>
