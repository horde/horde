<h1><?php echo $title ?></h1>

<ul class="notices">
<li><img src="<?php echo $registry->getImageDir('horde')?>/alerts/warning.png"><?php echo sprintf(_("User %s would like to his profile remains visible only to authenticated users."), $user) ?></li>
<?php
echo '<li><img src="' . $registry->getImageDir('horde') . '/alerts/success.png">'
                . _("Click here to login.")
                . ' <a href="' . Hode::getServiceLink('login', 'folks')  . '">' . _("Click here to login.") . '</a>'
                    . '</li>';
?>
</ul>
