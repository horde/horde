<div id="portal-page" data-role="page">
 <div data-role="header">
  <h1><?php echo sprintf(_("Welcome, %s"), $fullname) ?></h1>
<?php if ($logout = $registry->getServiceLink('logout')): ?>
  <a href="<?php echo $logout->setRaw(false) ?>" rel="external" class="ui-btn-right" data-theme="e" data-icon="delete"><?php echo _("Log out") ?></a>
<?php endif ?>
 </div>
 <div data-role="content">
  <ul data-role="listview">
<?php foreach ($links as $key => $val): ?>
   <li class="extlink"><?php echo $val[0]->link(array('rel' => 'external')) ?><img class="ui-li-icon" src="<?php echo $val[1] ?>"><?php echo $key ?></a></li>
<?php endforeach; ?>
  </ul>
 </div>
</div>
