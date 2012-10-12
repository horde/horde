<div class="header leftAlign">
 <?php echo _("Mailbox Sizes") ?>
</div>

<table class="striped sortable">
 <thead>
  <tr>
   <th class="leftAlign"><?php echo _("Mailbox") ?></th>
   <th class="leftAlign"><?php echo _("Size") ?></th>
  </tr>
 </thead>

 <tbody>
<?php foreach ($this->mboxes as $v): ?>
  <tr>
   <td class="leftAlign">
    <?php echo $this->h($v['name']) ?>
   </td>
   <td class="rightAlign" sortval="<?php echo $v['sort'] ?>">
    <?php echo $v['size'] ?>
   </td>
  </tr>
<?php endforeach; ?>
 </tbody>

 <tfoot>
  <tr>
   <td class="leftAlign">
    <strong><?php echo _("Sum") ?></strong>
   </td>
   <td class="rightAlign">
    <strong><?php echo $this->mboxes_sum ?></strong>
   </td>
  </tr>
 </tfoot>
</table>

<form name="returnform">
 <div class="control leftAlign">
  <input id="btn_return" type="button" class="button" value="<?php echo _("Return to Folders View") ?>" />
 </div>
</form>
