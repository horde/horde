<form name="newpage" method="post" action="<?php echo $this->action ?>">
<?php echo $this->formInput ?>
<input type="hidden" name="page" value="NewPage" />
<input type="hidden" name="referrer" value="<?php echo $this->h($this->referrer) ?>" />

<h1 class="header">
 <?php echo $this->h($this->name) ?>
</h1>

<?php if ($this->pages): ?>
<p class="horde-content">
 <?php printf(_("\"%s\" does not exist, but maybe you were looking for one of the following pages?"), $this->h($this->referrer)) ?>
</p>
<table class="horde-table sortable" style="width:100%">
 <thead>
  <tr>
   <th style="width:40%"><?php echo _("Page") ?></th>
   <th style="width:10%"><?php echo _("Version") ?></th>
   <th style="width:25%"><?php echo _("Author") ?></th>
   <th style="width:25%"><?php echo _("Creation Date") ?></th>
  </tr>
 </thead>
 <tbody>
<?php echo $this->renderPartial('pagelist/page', array('collection' => $this->pages))  ?>
 </tbody>
</table>
<br />
<p class="horde-content">
 <?php echo _("Click on \"Create\" below if you want to create this page now and start editing.") ?>
</p>
<?php else: ?>
<p class="horde-content">
 <?php printf(_("%s does not exist. Click on \"Create\" below if you want to create this page now and start editing."), $this->h($this->referrer)) ?>
</p>
<?php endif; ?>

<p class="horde-content">
 <?php echo _("Page Template:") ?>
 <select name="template">
  <option value=""><?php echo _("(None)") ?></option>
<?php foreach ($this->templates as $page): ?>
  <option value="<?php echo $this->h($page['page_name']) ?>"><?php echo $this->h($page['page_name']) ?></option>
<?php endforeach ?>
 </select>

 <input class="horde-create" type="submit" value="<?php echo _("Create") ?>" />
 <a class="horde-cancel" href="#" onclick="window.history.back();"><?php echo _("Cancel") ?></a>
 <?php echo $this->help ?>
</p>

</form>
