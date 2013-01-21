<!-- Tag Widget -->
<?php echo $this->render('begin'); ?>
<div id="tags">
<?php if (!empty($this->error_text)): ?>
  <?php echo $this->error_text; ?>
<?php else: ?>
  <?php echo $this->tag_html ?>
  <?php if (!empty($this->have_edit)): ?>
    <form name="tagform" action="<?php echo $this->action_url?>" onsubmit="return AnselTagActions.submitcheck();" method="post">
      <input id="actionID" name="actionID" type="hidden" value="addTags" />
      <input id="addtag" name="addtag" type="text" size="15" /> <input name="tagbutton" id="tagbutton" class="button" value="<?php echo _("Add")?>" type="submit" />
    </form>
  <?php endif; ?>
<?php endif; ?>
</div>
<?php echo $this->render('end'); ?>
<!-- End Tag Widget -->