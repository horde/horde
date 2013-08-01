<div id="portal-page" data-role="page">
 <div data-role="header">
  <h1><?php echo sprintf(_("Welcome, %s"), htmlspecialchars($fullname)) ?></h1>
<?php if ($registry->showService('logout') && ($logout = $registry->getServiceLink('logout'))): ?>
  <a href="<?php echo $logout->setRaw(false) ?>" data-ajax="false" class="ui-btn-right" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>

 <div data-role="content">
<?php if (!empty($mobile_links)): ?>
  <div data-role="collapsible" data-collapsed="false" data-theme="b" data-inset="false">
   <h4><?php echo _("Mobile Optimized Apps") ?></h4>
   <ul data-role="listview">
<?php foreach ($mobile_links as $key => $val): ?>
    <li><?php echo $val[0]->link(array('data-ajax' => 'false')) ?><img class="ui-li-icon" src="<?php echo $val[1] ?>"><?php echo htmlspecialchars($key) ?></a></li>
<?php endforeach; ?>
   </ul>
  </div>
<?php endif; ?>
<?php if (!empty($links)): ?>
  <div data-role="collapsible" data-theme="b" data-inset="false">
   <h4><?php echo _("Regular Apps") ?></h4>
   <ul data-role="listview">
<?php foreach ($links as $key => $val): ?>
    <li><?php echo $val[0]->link(array('data-ajax' => 'false')) ?><img class="ui-li-icon" src="<?php echo $val[1] ?>"><?php echo htmlspecialchars($key) ?></a></li>
<?php endforeach; ?>
   </ul>
  </div>
<?php endif; ?>
 </div>
</div>
