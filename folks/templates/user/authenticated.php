<h1><?php echo $title ?></h1>

<ul class="notices">
<li><img src="<?php echo $registry->getImageDir('horde')?>/alerts/warning.png"><?php echo sprintf(_("User %s would like to his profile remains visible only to authenticated users."), $user) ?></li>
<?php
echo '<li><img src="' . $registry->getImageDir('horde') . '/alerts/success.png">'
                . _("Click here to login.")
                . ' <a href="' . Horde_Auth::getLoginScreen('letter', Horde_Util::addParameter(Horde::applicationUrl('user.php'), 'user', $user))  . '">' . _("Click here to login.") . '</a>'
                    . '</li>';
?>
</ul>
