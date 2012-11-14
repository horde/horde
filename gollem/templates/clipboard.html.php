<form method="post" name="gollem-clipboard" id="gollem-clipboard" enctype="multipart/form-data" action="<?php echo $this->manager_url ?>">
<?php echo $this->forminput ?>
<input type="hidden" name="actionID" id="actionID" value="" />
<input type="hidden" name="dir" value="<?php echo $this->dir ?>" />

<h1 class="header">
 <strong><?php echo _("Clipboard") ?></strong>
</h1>

<div class="horde-content">
<p>
 <?php echo _("Below is the current contents of your clipboard.") ?>
</p>

<p>
 <?php echo _("To paste items from the clipboard to the current directory, check the box next to the filename and click on &quot;Paste&quot;.") ?>
 <br />
 <?php echo _("To clear items from the clipboard, check the box next to the filename and click on &quot;Clear&quot;.") ?>
</p>

<p>
 <?php echo _("Current directory:") ?> <span class="fixed"><?php echo $this->currdir ?></span>
</p>
</div>

<table class="horde-table nowrap">
<thead>
 <tr>
  <th>
   <label>
    <input id="gollem-selectall" type="checkbox" class="checkbox" />
    <span class="gollem-selectall"><?php echo _("Select All") ?></span>
   </label>
  </th>
 </tr>
</thead>
<tbody>
<?php foreach ($this->entries as $entry): ?>
 <tr>
  <td>
   <label>
    <input type="checkbox" class="checkbox" name="items[]" value="<?php echo $entry['id'] ?>" />
    <?php if ($entry['cut']) echo $this->cutgraphic ?>
    <?php if ($entry['copy']) echo $this->copygraphic ?>
    <?php echo $entry['name'] ?>
   </label>
  </td>
 </tr>
<?php endforeach ?>
</tbody>
</table>

<p class="horde-form-buttons">
 <input class="horde-default" id="gollem-pastebutton" type="button" value="<?php echo $this->pastebutton ?>" />
 <input id="gollem-clearbutton" type="button" value="<?php echo $this->clearbutton ?>" />
 <input class="horde-cancel" id="gollem-cancelbutton" type="button" value="<?php echo $this->cancelbutton ?>" />
</p>

</form>
