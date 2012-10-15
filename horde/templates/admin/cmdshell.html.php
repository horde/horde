<?php if ($this->out): ?>
<h1 class="header"><?php echo _("Command") ?>:</h1>
<div class="horde-content">
 <code><?php echo nl2br($this->h($this->command)) ?></code>
</div>

<h1 class="header"><?php echo _("Results") ?>:</h1>
<div class="horde-content">
 <pre class="text"><?php echo $this->h(implode('', $this->out)) ?></pre>
</div>
<?php endif; ?>

<form action="<?php echo $this->action ?>" method="post">
 <h1 class="header"><?php echo $this->title ?></h1>

 <div class="horde-content">
  <label for="cmd" class="hidden"><?php echo _("Command") ?></label>
  <textarea class="fixed" id="cmd" name="cmd" rows="10" cols="80"><?php echo $this->h($this->command) ?></textarea>
 </div>

 <div class="horde-form-buttons">
  <input type="submit" class="horde-default" value="<?php echo _("Execute") ?>" />
  <?php echo $this->hordeHelp('admin', 'admin-cmdshell') ?>
 </div>
</form>
