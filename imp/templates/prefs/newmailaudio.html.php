<div>
 <p>
  <?php echo _("Play a sound when receiving new mail? Although most browsers support embedded sound files, some may require a plugin.") ?>
 </p>
 <ul class="sound-list">
  <li>
   <label>
    <?php echo $this->radioButtonTag('newmail_audio', '', !$this->newmail_audio) ?>
    <?php echo _("No Sound") ?>
   </label>
  </li>
<?php foreach ($this->sounds as $v): ?>
  <li>
   <label>
    <?php echo $this->radioButtonTag('newmail_audio', $v['v'], $v['c']) ?>
    <?php echo $this->h($v['l']) ?>
   </label>
   <embed autostart="false" src="<?php echo $this->h($v['s']) ?>" />
  </li>
<?php endforeach; ?>
 </ul>
</div>
