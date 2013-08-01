<?php if ($this->session_info): ?>
<h1 class="header"><?php echo _("Current Sessions") . ' (' . count($this->session_info) . ')' ?></h1>

<ul class="linedRow">
<?php foreach ($this->session_info as $v): ?>
 <li>
  <div class="sesstoggle">
   <?php echo $this->hordeImage('tree/plusonly.png', _("Expand")) ?>
   <?php echo $this->hordeImage('tree/minusonly.png', _("Collapse"), 'style="display:none"') ?>
   <?php echo $this->h($v['userid']) ?>
   [<?php echo $this->h($v['id']) ?>]
  </div>
  <div style="padding-left:20px;display:none">
   <div>
    <strong><?php echo _("Session Timestamp") ?>:</strong>
    <?php echo $this->h($v['timestamp']) ?>
   </div>
   <div>
    <strong><?php echo _("Browser") ?>:</strong>
    <?php echo $this->h($v['browser']) ?>
   </div>
   <div>
    <strong><?php echo _("Remote Host") ?>:</strong>
    <?php echo $this->h($v['remotehost']) ?> <?php echo $v['remotehostimage'] ?>
   </div>
   <div>
    <strong><?php echo _("Authenticated to") ?>:</strong>
    <?php echo $this->h($v['auth']) ?>
   </div>
  </div>
 </li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<h1 class="header"><?php echo _("Current Sessions") ?></h1>
<p class="headerbox">
 <em><?php printf(_("Listing sessions failed: %s"), $this->error) ?></em>
</p>
<?php endif; ?>
