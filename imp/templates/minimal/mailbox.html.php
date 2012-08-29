<?php if ($this->checkbox): ?>
<form action="<?php echo $this->checkbox ?>" method="post">
 <input type="hidden" name="mt" value="<?php echo $this->mt ?>" />
<?php endif; ?>

<?php if ($this->msgs): ?>
 <table>
  <tr>
<?php if ($this->checkbox): ?>
   <th></th>
<?php endif; ?>
   <th></th>
   <th><?php echo _("From") ?></th>
   <th><?php echo _("Subject") ?></th>
  </tr>
<?php foreach ($this->msgs as $v): ?>
  <tr>
<?php if ($this->checkbox): ?>
   <td>
    <input type="checkbox" name="indices[]" value="<?php echo $v['uid'] ?>" />
   </td>
<?php endif; ?>
   <td><?php echo $v['status'] ?></td>
   <td><?php echo $this->h($this->truncate($v['from']), 50) ?></td>
   <td><a href="<?php echo $v['target'] ?>"><?php echo $this->h($this->truncate($v['subject'], 50)) ?></a></td>
  </tr>
<?php endforeach; ?>
 </table>

 <hr />

 <div>
  <select name="checkbox">
   <option value="" selected="selected"><?php echo _("Action") ?></option>
<?php if ($this->delete): ?>
   <option value="d">&nbsp;<?php echo _("Delete") ?></option>
   <option value="u">&nbsp;<?php echo _("Undelete") ?></option>
<?php endif; ?>
   <option value="rs">&nbsp;<?php echo _("Report As Spam") ?></option>
   <option value="ri">&nbsp;<?php echo _("Report As Innocent") ?></option>
  </select>
  <input type="submit" value="<?php echo _("Do Action") ?>" />
 </div>

</form>
<?php else: ?>
<div><?php echo _("No messages.") ?></div>
<?php endif; ?>
