<h1 class="header"><?php echo _("Status") ?></h1>
<?php if ($registry->getAuth()): ?>
<br class="spacer" />
<table class="horde-table">
  <tr><th colspan="2"><?php echo _("People watching") ?></th></tr>
<?php if ($listeners): ?>
<?php foreach ($listeners as $listener): ?>
  <tr>
    <td><?php echo htmlspecialchars($listener) ?></td>
    <td><?php echo $delurl->add('del_listener', $listener)->link() . $delimg . '</a>' ?></td>
  </tr>
<?php endforeach ?>
<?php else: ?>
  <tr><td><em><?php echo _("No people watching") ?></em></td></tr>
<?php endif ?>
</table>
<br class="spacer" />
<table class="horde-table">
  <tr><th><?php echo _("People responsible") ?></th></tr>
<?php if ($owners): ?>
<?php foreach ($owners as $owner): ?>
  <tr><td><?php echo htmlspecialchars(Whups::formatUser($owner)) ?></td></tr>
<?php endforeach ?>
<?php else: ?>
  <tr><td><em><?php echo _("No people responsible") ?></em></td></tr>
<?php endif ?>
</table>
<?php else: ?>
<p class="horde-content">
<?php
printf(
  _("%d people watching, %d people responsible"),
  count($listeners),
  count($owners)
)
?>
</p>
<?php endif ?>
<br class="spacer" />
