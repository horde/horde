<div id="target" data-role="dialog">
 <div data-role="header">
  <h1 id="imp-target-header"></h1>
 </div>

 <div data-role="content" class="ui-body">
  <form id="imp-target">
   <input id="imp-target-mbox" type="hidden" />
   <input id="imp-target-uid" type="hidden" />
   <select id="imp-target-list">
    <?php echo $this->options ?>
   </select>
   <div id="imp-target-newdiv">
    <label for="imp-target-new"><?php echo _("New mailbox name:") ?></label>
    <input id="imp-target-new" type="text" />
    <input id="imp-target-new-submit" type="button" data-theme="a" value="<?php echo _("Create") ?>" />
   </div>
   <a href="#" data-role="button" data-rel="back"><?php echo _("Cancel") ?></a>
  </form>
 </div>
</div>
