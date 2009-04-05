<?= $this->renderPartial('header'); ?>
<?= $this->renderPartial('menu'); ?>

<?= $this->tabs->render($this->object_type); ?>

<?php if (isset($this->objectlist)): ?>

<table cellspacing="0" width="100%" class="linedRow">
 <thead>
  <tr>
   <th class="item" width="1%"><?php echo Horde::img('edit.png', _("Edit"), '', $GLOBALS['registry']->getImageDir('horde')) ?></th>
   <th class="item" width="1%"><?php echo Horde::img('delete.png', _("Delete"), '', $GLOBALS['registry']->getImageDir('horde')) ?></th>
   <?php foreach ($this->attributes as $attribute => $info): ?>
     <th class="item leftAlign" width="<?php echo $info['width'] ?>%" nowrap="nowrap"><?= $info['title'] ?></th>
   <?php endforeach; ?>
  </tr>
 </thead>
 <tbody>
  <?php foreach ($this->objectlist as $dn => $info): ?>
  <tr>
   <td>
    <?= $info['edit_url'] ?>
   </td> 
   <td>
    <?= $info['delete_url'] ?>
   </td> 
   <?php foreach ($this->attributes as $attribute => $ainfo): ?>
   <td>
   <?php if (!empty($ainfo['link_view'])): ?>
   <?= $info['view_url'] . $this->escape($info[$attribute]) . '</a>'; ?>
   <?php else: ?>
    <?= $this->escape($info[$attribute]) ?>
   <?php endif; ?>
   </td> 
   <?php endforeach; ?>
  </tr> 
  <?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>
