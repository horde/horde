<div id="browse" data-role="page">
 <?php echo $this->smartmobileHeader(array('logout' => true, 'portal' => true, 'title' => _("Browse"))) ?>

 <div data-role="content">
<?php if (empty($this->list)): ?>
  <ul data-role="listview">
   <li><?php echo _("No browseable address books") ?></li>
  </ul>
<?php else: ?>
<?php foreach ($this->list as $k => $v): ?>
  <div data-role="collapsible" data-theme="b" data-inset="false">
   <h3><?php echo $this->h($k) ?></h3>
   <ul data-role="listview" data-filter="true">
<?php foreach ($v as $v2): ?>
    <li>
     <a href="<?php echo $v2['url'] ?>">
<?php if (!empty($v2['group'])): ?>
      <?php echo $this->hordeImage('group.png', '', 'class="ui-li-icon"') ?>
<?php endif; ?>
      <?php echo $this->h($v2['name']) ?>
     </a>
    </li>
<?php endforeach; ?>
   </ul>
  </div>
<?php endforeach; ?>
<?php endif; ?>
 </div>
</div>
