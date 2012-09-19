<input type="hidden" name="acl_mbox" value="<?php echo $this->mbox ?>" />

<?php if ($this->hasacl): ?>
<div>
 <h3><?php echo $this->current ?></h3>
</div>

<table class="prefsAclTable">
 <tr>
  <th class="item" width="<?php echo $this->width ?>">
   <h4>
    <strong><?php echo _("User") ?></strong>
   </h4>
  </th>
<?php foreach ($this->rights as $v): ?>
  <th class="item" width="<?php echo $this->width ?>">
   <h4>
    <span class="prefsAclHeader" title="<?php echo $v['desc'] ?>"><?php echo $v['title'] ?></span>
   </h4>
  </th>
<?php endforeach; ?>
 </tr>
<?php foreach ($this->curr_acl as $v): ?>
 <tr>
  <td class="item">
<?php if (empty($v['negative'])): ?>
   <?php echo $this->h($v['index']) ?>
<?php else: ?>
   <span class="prefsAclNegative"><?php echo $this->h($v['negative']) ?></span>
   <span class="prefsAclNegativeLabel"><?php echo _("Negative Right") ?></span>
<?php endif; ?>
  </td>
<?php foreach ($v['rule'] as $v2): ?>
  <td class="item" align="center">
   <?php echo $this->checkBoxTag('acl[' . $v['index'] . '][]', $v2['val'], $v2['on'], array('disabled' => $v2['disable'])) ?>
  </td>
<?php endforeach; ?>
 </tr>
<?php endforeach; ?>

<?php if ($this->canedit): ?>
 <tr>
  <td class="item">
   <label for="new_user" class="hidden"><?php echo _("New User") ?></label>
<?php if ($this->new_user): ?>
   <select id="new_user" name="new_user">
<?php foreach ($this->new_user as $v): ?>
    <option><?php echo $v ?></option>
<?php endforeach; ?>
   </select>
<?php else: ?>
   <input id="new_user" type="text" name="new_user" />
<?php endif; ?>
  </td>
<?php foreach ($this->rights as $v): ?>
  <td class="item" align="center">
   <?php echo $this->checkBoxTag('new_acl[]', $v['val']) ?>
 </td>
<?php endforeach; ?>
 </tr>
<?php endif; ?>
</table>
<?php endif; ?>

<div>
 <span class="iconImg folderImg"></span>
 <select id="aclmbox" name="mbox">
  <?php echo $this->options ?>
 </select>
 <input type="submit" name="change_acl_mbox" id="change_acl_mbox" class="button" value="<?php echo _("Change") ?>" />
</div>
