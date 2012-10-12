<div id="rules" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Rules"))) ?>

 <div data-role="content">
  <ul data-role="listview" data-filter="true" id="ingo-rules-list">
<?php if (empty($this->list)): ?>
   <li><?php echo _("No rules") ?></li>
<?php else: ?>
<?php foreach ($this->list as $v): ?>
   <li>
    <a href="<?php echo $v['url'] ?>">
     <?php echo $v['img'] ?>
     <?php echo $this->h($v['name']) ?>
    </a>
   </li>
<?php endforeach; ?>
<?php endif; ?>
  </ul>
 </div>
</div>
