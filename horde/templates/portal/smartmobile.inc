<div id="portal-page" data-role="page">
 <div data-role="header">
  <h1><?php echo sprintf(_("Welcome, %s"), $fullname) ?></h1>
<?php if ($logout = $registry->getServiceLink('logout')): ?>
  <a href="<?php echo $logout->setRaw(false) ?>" data-ajax="false" class="ui-btn-right" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>
 <div data-role="content">
  <ul data-role="listview">
<?php if (!empty($mobile_links)): ?>
   <li data-role="list-divider"><?php echo _("Mobile Optimized Apps") ?></li>
<?php foreach ($mobile_links as $key => $val): ?>
   <li><?php echo $val[0]->link(array('data-ajax' => 'false')) ?><img class="ui-li-icon" src="<?php echo $val[1] ?>"><?php echo htmlspecialchars($key) ?></a></li>
<?php endforeach; ?>
<?php endif; ?>
<?php if (!empty($links)): ?>
   <li data-role="list-divider"><?php echo _("Regular Apps") ?></li>
<?php foreach ($links as $key => $val): ?>
   <li><?php echo $val[0]->link(array('data-ajax' => 'false')) ?><img class="ui-li-icon" src="<?php echo $val[1] ?>"><?php echo htmlspecialchars($key) ?></a></li>
<?php endforeach; ?>
<?php endif; ?>
  </ul>
 </div>
</div>
