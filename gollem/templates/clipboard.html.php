<form method="post" name="clipboard" id="clipboard" enctype="multipart/form-data" action="<?php echo $this->manager_url ?>">
<?php echo $this->forminput ?>
<input type="hidden" name="actionID" id="actionID" value="" />
<input type="hidden" name="dir" value="<?php echo $this->dir ?>" />

<h1 class="header">
 <strong><?php echo _("Clipboard") ?></strong>
</h1>

<div class="control leftAlign">
 <?php echo _("Below is the current contents of your clipboard.") ?>
</div>

<div class="control leftAlign">
 <?php echo _("To paste items from the clipboard to the current directory, check the box next to the filename and click on &quot;Paste&quot;.") ?>
 <br />
 <?php echo _("To clear items from the clipboard, check the box next to the filename and click on &quot;Clear&quot;.") ?>
</div>

<div class="control leftAlign">
 <?php echo _("Current directory:") ?> <span class="fixed"><?php echo $this->currdir ?></span>
</div>

<table class="clipboard striped nowrap" width="100%" cellspacing="0">
<thead>
 <tr>
  <td>
   <label><input id="selectall" type="checkbox" class="checkbox" /> <span class="selectall"><?php echo _("Select All") ?></span></label>
  </td>
   </tr>
</thead>
<tbody>
<?php foreach ($this->entries as $entry): ?>
 <tr>
  <td>
   <label><input type="checkbox" class="checkbox" name="items[]" value="<?php echo $entry['id'] ?>" />
   <?php if ($entry['cut']) echo $this->cutgraphic ?>
   <?php if ($entry['copy']) echo $this->copygraphic ?>
   <?php echo $entry['name'] ?></label>
  </td>
 </tr>
<?php endforeach ?>
</tbody>
<tfoot>
 <tr>
  <td>
   <input class="horde-default" id="pastebutton" type="button" value="<?php echo $this->pastebutton ?>" />
   <input id="clearbutton" type="button" value="<?php echo $this->clearbutton ?>" />
   <input class="horde-cancel" id="cancelbutton" type="button" value="<?php echo $this->cancelbutton ?>" />
  </td>
 </tr>
</tfoot>
</table>

</form>
