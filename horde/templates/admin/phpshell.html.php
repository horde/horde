<form action="<?php echo $this->action ?>" method="post">
 <h1 class="header"><?php echo $this->h($this->title) ?></h1>

 <div class="horde-content">
  <p>
   <label for="app"><?php echo _("Application Context") ?>:</label>
   <select id="app" name="app">
<?php foreach ($this->apps as $app => $name): ?>
    <option value="<?php echo $app ?>"<?php if ($this->application == $app) echo ' selected="selected"' ?>><?php echo $name ?></option>
<?php endforeach; ?>
   </select>
  </p>

  <p>
   <label for="php" class="hidden"><?php echo _("PHP") ?></label>
   <textarea class="fixed" id="php" name="php" rows="10" cols="80"><?php echo $this->h($this->command) ?></textarea>
  </p>
 </div>

 <p class="horde-form-buttons">
  <input type="submit" class="horde-default" value="<?php echo _("Execute") ?>" />
  <?php echo $this->hordeHelp('admin', 'admin-phpshell') ?>
 </p>
</form>

<?php if ($this->command): ?>
<h1 class="header"><?php echo _("PHP Code") ?></h1>
<div class="horde-content">
 <?php echo $this->pretty ?>
</div>

<h1 class="header"><?php echo _("Results") ?></h1>
<div class="horde-content">
 <pre class="text"><?php echo $this->command_exec ?></pre>
</div>
<?php endif; ?>
