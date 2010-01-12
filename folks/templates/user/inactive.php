<h1><?php echo $title ?></h1>

<ul class="notices">
<li><img src="<?php echo $registry->getImageDir('horde')?>/alerts/warning.png"><?php echo sprintf(_("User %s is inactive."), $user) ?></li>
</ul>
