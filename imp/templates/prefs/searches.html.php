<input type="hidden" name="searches_action" id="searches_action" />
<input type="hidden" name="searches_data" id="searches_data" />

<?php if (!empty($this->vfolders)): ?>
<table class="searchesmanagement">
 <thead>
  <tr>
   <th><?php echo _("Virtual Folder") ?></th>
   <th><?php echo _("Enabled?") ?></th>
   <th><?php echo _("Actions") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->vfolders as $v): ?>
  <tr>
   <td>
<?php if ($v['enabled']): ?>
<?php if ($v['m_url']): ?>
    <?php echo $v['m_url'] ?><?php echo $this->h($v['label']) ?></a>
<?php else: ?>
    <span class="vfolderenabled"><?php echo $this->h($v['label']) ?></span>
<?php endif; ?>
<?php else: ?>
    <?php echo $this->h($v['label']) ?>
<?php endif; ?>
   </td>
   <td class="enabled">
    <?php echo $this->checkBoxTag('enable_' . $v['key'], 1, $v['enabled'], array('disabled' => $v['enabled_locked'])) ?>
   </td>
   <td>
<?php if ($v['edit']): ?>
    <a class="vfolderedit" href="<?php echo $v['edit'] ?>"><span class="iconImg editImg"></span></a>
    <a class="vfolderdelete" href="#"><span class="iconImg deleteImg"></span></a>
<?php else: ?>
    <?php echo _("No Actions Available") ?>
<?php endif; ?>
   </td>
  </tr>
  <tr>
   <td colspan="3" class="fixed searchdescription">
    <?php echo $this->h($v['description']) ?>
   </td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>

<?php if (!empty($this->filters)): ?>
<table class="searchesmanagement">
 <thead>
  <tr>
   <th><?php echo _("Filter") ?></th>
   <th><?php echo _("Enabled?") ?></th>
   <th><?php echo _("Actions") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->filters as $v): ?>
  <tr>
   <td>
    <?php echo $this->h($v['label']) ?>
   </td>
   <td class="enabled">
    <?php echo $this->checkBoxTag('enable_' . $v['key'], 1, $v['enabled'], array('disabled' => $v['enabled_locked'])) ?>
   </td>
   <td>
<?php if ($v['edit']): ?>
    <a class="filteredit" href="<?php echo $v['edit'] ?>"><span class="iconImg editImg"></span></a>
    <a class="filterdelete" href="#"><span class="iconImg deleteImg"></span></a>
<?php else: ?>
    <?php echo _("No Actions Available") ?>
<?php endif; ?>
   </td>
  </tr>
  <tr>
   <td colspan="3" class="fixed searchdescription">
    <?php echo $this->h($v['description']) ?>
   </td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>

<?php if ($this->nosearches): ?>
<div>
 <em><?php echo _("No Saved Searches Defined.") ?></em>
</div>
<?php endif; ?>
