<div data-role="page" id="tasklist">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("My Tasks"))) ?>

 <div data-role="content">
  <ul data-role="listview">
<?php foreach ($this->li as $v): ?>
   <li<?php if ($v['style']): ?> class="<?php echo $v['style'] ?>"<?php endif; ?>>
    <a data-rel="dialog" data-transition="slideup" href="<?php echo $v['href'] ?>">
     <h3><?php echo $this->h($v['name']) ?></h3>
     <p><?php echo $this->h($v['desc']) ?></p>
     <p class="ui-li-aside<?php if ($v['overdue']): ?> overdue<?php endif; ?>">
      <strong><?php echo $v['due'] ?></strong>
     </p>
    </a>
    <a data-task="<?php echo $this->h($v['id']) ?>" data-tasklist="<?php echo $this->h($v['tasklist']) ?>" data-icon="<?php echo $v['icon'] ?>" href="#"<?php if ($v['tcc']): ?> class="<?php echo $v['tcc'] ?>"<?php endif; ?>><?php echo $v['label'] ?></a>
   </li>
<?php endforeach; ?>
  </ul>
 </div>

<?php if ($this->create_form): ?>
 <div data-role="footer" data-id="nag-footer" data-position="fixed">
  <div data-role="navbar">
   <ul>
    <li><a href="#create" data-rel="dialog" data-transition="slideup" data-icon="plus"><?php echo _("New") ?></a></li>
   </ul>
  </div>
 </div>
<?php endif; ?>

</div>
