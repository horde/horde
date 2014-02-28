<form action="<?php echo $this->version_action ?>" style="margin:8px">
 <?php echo $this->version_input ?>
 <input type="hidden" value="1" name="check_versions" />
 <input type="submit" value="<?php echo _("Check for newer versions") ?>" />
</form>

<?php if ($this->config_outdated): ?>
<form action="<?php echo $this->version_action ?>" style="margin:8px">
 <?php echo $this->version_input ?>
 <input type="hidden" value="config" name="action" />
 <input type="submit" value="<?php echo _("Update all configurations") ?>" />
</form>
<?php endif; ?>

<?php if ($this->schema_outdated): ?>
<form action="<?php echo $this->version_action ?>" style="margin:8px">
 <?php echo $this->version_input ?>
 <input type="hidden" value="schema" name="action" />
 <input type="submit" value="<?php echo _("Update all DB schemas") ?>" />
</form>
<?php endif; ?>

<table class="horde-table" cellspacing="0" width="100%">
 <thead>
  <tr>
   <th>
    <?php echo _("Application") ?>
   </th>
   <th>
    <?php echo _("Database") ?>
   </th>
   <th>
    <?php echo _("Status") ?>
   </th>
<?php if ($this->versions): ?>
   <th>
    <?php echo _("Version Check") ?>
   </th>
<?php endif; ?>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->apps as $v): ?>
  <tr>
   <td>
    <?php echo $v['icon'] ?>
    <?php echo $v['name'] ?>
    <?php echo $v['version'] ?>
   </td>
   <td>
<?php if (!empty($v['db'])): ?>
<?php for ($i = 0; $i < count($v['db']); ++$i): ?>
    <?php echo $v['db'][$i] ?>
    <?php echo $v['dbstatus'][$i] ?>
    <br />
<?php endfor; ?>
<?php endif; ?>
   </td>
   <td>
    <?php if (isset($v['conf'])) echo $v['conf'] ?>
    <?php if (isset($v['status'])) echo $v['status'] ?>
   </td>
<?php if ($this->versions): ?>
   <td>
    <?php echo $v['load'] ?>
    <?php echo $v['vstatus'] ?>
   </td>
<?php endif; ?>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>

<table id="update" cellspacing="10">
 <tr valign="top">
  <td width="50%">
<?php if ($this->actions): ?>
   <h1 class="header">
    <?php echo _("Configuration upgrade scripts available") ?>
   </h1>
   <table class="headerbox" width="100%">
<?php foreach ($this->actions as $v): ?>
    <tr>
     <td><?php echo $v['icon'] ?></td>
     <td><?php echo $v['link'] ?></td>
    </tr>
<?php endforeach; ?>
   </table>
<?php endif; ?>
  </td>
  <td width="50%">
<?php if ($this->ftpform): ?>
   <h1 class="header"><?php echo _("FTP upload of configuration") ?></h1>
   <div class="headerbox">
    <?php echo $this->ftpform ?>
   </div>
<?php endif; ?>
  </td>
 </tr>
</table>
