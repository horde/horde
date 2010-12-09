<div id="quickAddInfoPanel" class="quickAddInfoPanel" style="display:none;">
 <h2><?php echo _("Quick Task Creation") ?></h2>
 <form method="post" action="quick.php">
  <p><?php echo _("Enter one task per line. Create child tasks by indenting them below their parent task. Include due dates like \"laundry tomorrow\" or \"get dry cleaning next Thursday\".") ?></p>
  <textarea name="quickText" id="quickText"></textarea>
  <p>
   <input type="submit" class="button" value="<?php echo _("Create") ?>" />
   <input type="button" class="button" onclick="RedBox.close()" value="<?php echo _("Cancel") ?>" />
  </p>
 </form>
</div>
