<h1 class="header"><?php echo $this->title ?></h1>

<form name="sqlshell" action="<?php echo $this->action ?>" method="post">
 <div class="horde-content">
<?php if ($this->results): ?>
<?php if ($this->command): ?>
  <h1 class="header"><?php echo _("Query") ?></h1>
  <pre class="text"><?php echo $this->h($this->command) ?></pre>
<?php endif; ?>

  <h1 class="header"><?php echo _("Results") ?></h1>

<?php if ($this->success): ?>
  <p>
   <strong><?php echo _("Success") ?></strong>
  </p>
<?php elseif (isset($this->keys)): ?>
  <table cellspacing="1" class="item striped">
   <tr>
<?php foreach ($this->keys as $k): ?>
    <th align="left"><?php echo $this->h($k) ?></th>
<?php endforeach; ?>
   </tr>
<?php foreach ($this->rows as $v): ?>
   <tr>
<?php foreach ($v as $v2): ?>
    <td class="fixed"><?php echo $this->h($v2) ?></td>
<?php endforeach; ?>
   </tr>
<?php endforeach; ?>
  </table>
<?php endif; ?>

<?php if (count($this->q_cache)): ?>
  <p>
   <label for="query_cache" class="hidden"><?php echo ("Query cache") ?></label>
   <select id="query_cache" name="query_cache" onchange="document.sqlshell.sql.value = document.sqlshell.query_cache[document.sqlshell.query_cache.selectedIndex].value;">
<?php foreach ($this->q_cache as $q): ?>
    <option value="<?php echo $this->h($q) ?>"><?php echo $this->h($q) ?></option>
<?php endforeach; ?>
   </select>
   <input type="button" value="<?php echo _("Paste") ?>" onclick="document.sqlshell.sql.value = document.sqlshell.query_cache[document.sqlshell.query_cache.selectedIndex].value;">
   <input type="button" value="<?php echo _("Run") ?>" onclick="document.sqlshell.sql.value = document.sqlshell.query_cache[document.sqlshell.query_cache.selectedIndex].value; document.sqlshell.submit();">
  </p>
<?php endif; ?>
<?php endif; ?>

  <p>
   <label for="sql" class="hidden"><?php echo ("SQL Query") ?></label>
   <textarea class="fixed" id="sql" name="sql" rows="10" cols="80"><?php echo $this->h($this->command) ?></textarea>
  </p>
 </div>

 <p class="horde-form-buttons">
  <input type="submit" class="horde-default" value="<?php echo _("Execute") ?>" />
  <input type="button" value="<?php echo _("Clear Query") ?>" onclick="document.sqlshell.sql.value=''" />
<?php if (strlen($this->command)): ?>
  <input type="reset" value="<?php echo _("Restore Last Query") ?>" />
<?php endif; ?>
  <input type="submit" name="list-tables" value="<?php echo _("List Tables") ?>" />
  <?php echo $this->hordeHelp('admin', 'admin-sqlshell') ?>
 </p>
</form>
