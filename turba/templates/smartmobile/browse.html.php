<div id="browse" data-role="page">
 <div data-role="header">
  <a data-ajax="false" href="<?php echo $this->portal ?>"><?php echo _("Applications")?></a>
  <h1><?php echo _("Browse") ?></h1>
<?php if ($this->logout): ?>
  <a href="<?php echo $this->logout ?>" data-ajax="false" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

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

 <div data-role="footer" class="ui-bar" data-position="fixed">
  <a href="" id="turba-browse-top" data-role="button" data-icon="arrow-u"><?php echo _("Top") ?></a>
 </div>
</div>
