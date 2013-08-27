<?php if ($this->session_info): ?>
<h1 class="header"><?php echo _("Current Sessions") . ' (' . count($this->session_info) . ')' ?></h1>

<table class="horde-table current-sessions striped sortable">
 <thead>
  <tr>
   <th><?php echo _("User") ?></th>
   <th><?php echo _("Session Timestamp") ?></th>
   <th><?php echo _("Browser") ?></th>
   <th><?php echo _("Remote Host") ?></th>
   <th><?php echo _("Authenticated Applications") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->session_info as $v): ?>
  <tr>
   <td><?php echo $this->h($v['userid']) ?> [<?php echo $this->h($v['id']) ?>]</td>
   <td><?php echo $this->h($v['timestamp']) ?></td>
   <td><?php echo $this->h($v['browser']) ?></td>
   <td><?php echo $this->h($v['remotehost']) ?> <?php echo $v['remotehostimage'] ?></td>
   <td><?php echo $this->h($v['auth']) ?></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
<?php else: ?>
<h1 class="header"><?php echo _("Current Sessions") ?></h1>
<p class="headerbox">
 <em><?php printf(_("Listing sessions failed: %s"), $this->error) ?></em>
</p>
<?php endif; ?>
