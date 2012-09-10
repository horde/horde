<div id="copymove" data-role="dialog">
 <div data-role="header">
  <h1><?php echo _("Copy/Move") ?></h1>
 </div>

 <div data-role="content" class="ui-body">
  <form id="imp-copymove">
   <input id="imp-copymove-mbox" type="hidden" />
   <input id="imp-copymove-uid" type="hidden" />
   <select id="imp-copymove-action">
    <option value="copy"><?php echo _("Copy") ?></option>
    <option value="move"><?php echo _("Move") ?></option>
   </select>
   <select id="imp-copymove-list">
    <?php echo $this->options ?>
   </select>
   <div id="imp-copymove-newdiv">
    <label for="imp-copymove-new"><?php echo _("New mailbox name:") ?></label>
    <input id="imp-copymove-new" type="text" />
    <a href="#copymove-new-submit" data-role="button" data-theme="a"><?php echo _("Create") ?></a>
   </div>
   <a href="#" data-role="button" data-rel="back"><?php echo _("Cancel") ?></a>
  </form>
 </div>
</div>
