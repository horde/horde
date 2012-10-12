<div id="browse" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Browse"))) ?>

 <div data-role="content">
  <ul data-role="listview" data-filter="true" id="turba-browse-list">
<?php if (empty($this->list)): ?>
   <li><?php echo _("No browseable address books") ?></li>
<?php else: ?>
<?php foreach ($this->list as $k => $v): ?>
   <li data-role="list-divider"><?php echo $this->h($k) ?></li>
<?php foreach ($v as $v2): ?>
   <li><a href="<?php echo $v2['url'] ?>"><?php echo $this->h($v2['name']) ?></a></li>
<?php endforeach; ?>
<?php endforeach; ?>
<?php endif; ?>
  </ul>
 </div>
</div>
