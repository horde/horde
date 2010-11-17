<h1><?php echo $title ?></h1>

<ul class="notices">
 <li>
  <?php echo Horde::img('alerts/warning.png') . sprintf(_("User %s would like to his profile remains visible only to authenticated users."), $user) ?>
 </li>
 <li>
  <?php echo Horde::img('alerts/success.png') . _("Click here to login.") ?> <a href="<?php echo Hode::getServiceLink('login', 'folks') ?>"><?php echo _("Click here to login.") ?></a>
 </li>
</ul>
