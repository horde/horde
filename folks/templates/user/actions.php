<br />

<div class="control folksActions" style="text-align: center;">

<?php if ($registry->hasInterface('letter')): ?>
<a href="<?php echo $registry->callByPackage('letter', 'compose', array($user)) ?>" title="<?php echo _("Send private message") ?>">
<?php echo Horde::img('letter.png') ?> <?php echo _("Send message") ?></a>

<a href="<?php echo $registry->get('webroot', 'letter') ?>/compose.php?title=<?php echo _("Look at this profile") ?>&content=<?php echo Folks::getUrlFor('user', $user, true, -1) ?>"  title="<?php echo _("Send this profile to a friend") ?>">
<?php echo Horde::img('/nav/right.png') ?> <?php echo _("Forward") ?></a>

<?php endif; ?>

<a href="javascript: document.getElementById('message_body').focus()" title="<?php echo _("Add a comment") ?>">
<?php echo Horde::img('agora.png', 'agora') ?> <?php echo _("Add a comment") ?></a>

<a href="<?php echo Horde_Util::addParameter(Horde::url('edit/friends/add.php'), 'user', $user); ?>" title="<?php echo sprintf(_("Add %s as a friend?"), $user) ?>" onclick="return confirm('<?php echo sprintf(_("Add %s as a friend?"), $user) ?>')">
<?php echo Horde::img('user.png') ?> <?php echo _("Friend") ?></a>

<a href="<?php echo Horde_Util::addParameter(Horde::url('edit/friends/blacklist.php'), 'user', $user); ?>" title="<?php echo sprintf(_("Add %s to you blacklist?"), $user) ?>" onclick="return confirm('<?php echo sprintf(_("Add %s to you blacklist?"), $user) ?>')">
<?php echo Horde::img('locked.png') ?> <?php echo _("Blacklist") ?></a>

<a href="<?php echo Horde_Util::addParameter(Horde::url('report.php'), 'user', $user); ?>" title="<?php echo _("Report user") ?>">
<?php echo Horde::img('problem.png') ?> <?php echo _("Report") ?></a>

<a href="<?php echo Folks::getUrlFor('list', 'list') ?>" title="<?php echo _("User list") ?>">
<?php echo Horde::img('group.png') ?> <?php echo _("Users") ?></a>

</div>

<br />
