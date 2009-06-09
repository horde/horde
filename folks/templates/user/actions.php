<br />

<div class="control folksActions" style="text-align: center;">

<?php if ($registry->hasInterface('letter')): ?>
<a href="<?php echo $registry->callByPackage('letter', 'compose', array($user)) ?>" title="<?php echo _("Send private message") ?>">
<img src="<?php echo $registry->getImageDir('letter') ?>/letter.png"> <?php echo _("Send message") ?></a>

<a href="<?php echo $registry->get('webroot', 'letter') ?>/compose.php?title=<?php echo _("Look at this profile") ?>&content=<?php echo Folks::getUrlFor('user', $user, true, -1) ?>"  title="<?php echo _("Send this profile to a friend") ?>">
<img src="<?php echo $registry->getImageDir('horde') ?>/nav/right.png"> <?php echo _("Forward") ?></a>

<?php endif; ?>

<a href="javascript: document.getElementById('message_body').focus()" title="<?php echo _("Add a comment") ?>">
<img src="<?php echo $registry->getImageDir('agora') ?>/agora.png"> <?php echo _("Add a comment") ?></a>

<a href="<?php echo Horde_Util::addParameter(Horde::applicationUrl('edit/friends/add.php'), 'user', $user); ?>" title="<?php echo sprintf(_("Add %s as a friend?"), $user) ?>" onclick="return confirm('<?php echo sprintf(_("Add %s as a friend?"), $user) ?>')">
<img src="<?php echo $registry->getImageDir('horde') ?>/user.png"> <?php echo _("Friend") ?></a>

<a href="<?php echo Horde_Util::addParameter(Horde::applicationUrl('edit/friends/blacklist.php'), 'user', $user); ?>" title="<?php echo sprintf(_("Add %s to you blacklist?"), $user) ?>" onclick="return confirm('<?php echo sprintf(_("Add %s to you blacklist?"), $user) ?>')">
<img src="<?php echo $registry->getImageDir('horde') ?>/locked.png"> <?php echo _("Blacklist") ?></a>

<a href="<?php echo Horde_Util::addParameter(Horde::applicationUrl('report.php'), 'user', $user); ?>" title="<?php echo _("Report user") ?>">
<img src="<?php echo $registry->getImageDir('horde') ?>/problem.png"> <?php echo _("Report") ?></a>

<a href="<?php echo Folks::getUrlFor('list', 'list') ?>" title="<?php echo _("User list") ?>">
<img src="<?php echo $registry->getImageDir('horde') ?>/group.png"> <?php echo _("Users") ?></a>

</div>

<br />
