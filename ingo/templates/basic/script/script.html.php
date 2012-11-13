<h1 class="header"><?php echo _("Script") ?></h1>
<table>
<?php if ($this->scriptexists): ?>
 <tr>
  <td align="center">
   <table class="scriptHeader">
    <tr>
<?php if ($this->showactivate): ?>
     <td>
      <form method="post" name="activate_script" action="<?php echo $this->scripturl ?>">
       <input type="hidden" name="actionID" value="action_activate" />
       <input class="horde-default" type="submit" name="submit" value="<?php echo _("Activate Script") ?>" />
      </form>
     </td>
     <td>
      <form method="post" name="deactivate_script" action="<?php echo $this->scripturl ?>">
       <input type="hidden" name="actionID" value="action_deactivate" />
       <input type="submit" name="submit" value="<?php echo _("Deactivate Script") ?>" />
      </form>
     </td>
     <td>
      <form method="post" name="show_active_script" action="<?php echo $this->scripturl ?>">
       <input type="hidden" name="actionID" value="show_active" />
       <input type="submit" name="submit" value="<?php echo _("Show Active Script") ?>" />
      </form>
     </td>
<?php else: ?>
     <td>
      <form method="post" name="show_current_script" action="<?php echo $this->scripturl ?>">
       <input type="submit" name="submit" value="<?php echo _("Show Current Script") ?>" />
      </form>
     </td>
<?php endif; ?>
    </tr>
   </table>
  </td>
 </tr>
</table>
<?php endif; ?>

<table>
 <tr>
  <td class="item">
   <pre>
<?php if ($this->scriptexists): ?>
<?php foreach ($this->lines as $k => $v): ?>
    <?php printf("%3d: %s\n", ++$k, $this->h($v)) ?>
<?php endforeach; ?>
<?php else: ?>
    [<?php echo _("No script generated.") ?>]
<?php endif; ?>
   </pre>
  </td>
 </tr>
</table>
