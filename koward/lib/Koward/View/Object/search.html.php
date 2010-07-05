<?php echo $this->renderPartial('header'); ?>
<?php echo $this->renderPartial('menu'); ?>

<?php echo $this->addBuiltinHelpers(); ?>

<?php if (empty($this->objectlist)): ?>
  <?php echo $this->form->renderActive(new Horde_Form_Renderer(), $this->vars,
                                $this->post, 'post'); ?>
<?php else: ?>
<table cellspacing="0" width="100%" class="linedRow">
 <thead>
  <tr>
   <?php if ($this->allowEdit): ?>
    <th class="item" width="1%"><?php echo Horde::img('edit.png', _("Edit")) ?></th>
   <?php endif; ?>
   <?php if ($this->allowDelete): ?>
    <th class="item" width="1%"><?php echo Horde::img('delete.png', _("Delete")) ?></th>
   <?php endif; ?>
   <?php foreach ($this->attributes as $attribute => $info): ?>
     <th class="item leftAlign" width="<?php echo $info['width'] ?>%" nowrap="nowrap"><?php echo $info['title'] ?></th>
   <?php endforeach; ?>
  </tr>
 </thead>
 <tbody>
  <?php foreach ($this->objectlist as $dn => $info): ?>
  <tr>
   <?php if ($this->allowEdit): ?>
    <td>
     <?php echo $info['edit_url'] ?>
    </td>
   <?php endif; ?>
   <?php if ($this->allowDelete): ?>
    <td>
     <?php echo $info['delete_url'] ?>
    </td>
   <?php endif; ?>
   <?php foreach ($this->attributes as $attribute => $ainfo): ?>
   <td>
   <?php
       if (isset($info[$attribute])) {
           if (is_array($info[$attribute])) {
               $value = $info[$attribute][0];
           } else {
               $value = $info[$attribute];
           }
       } else {
           $value = '';
       }
       ?>
   <?php if (!empty($ainfo['link_view'])): ?>
   <?php echo $info['view_url'] . $this->escape($value) . '</a>'; ?>
   <?php else: ?>
    <?php echo $this->escape($value) ?>
   <?php endif; ?>
   </td>
   <?php endforeach; ?>
  </tr>
  <?php endforeach; ?>
 </tbody>
</table>

<?php endif; ?>
