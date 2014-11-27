<?php if ($this->showTools): ?>
<form name="display" action="#">
<div class="horde-buttonbar">
  <ul>
<?php if ($this->edit): ?>
    <li class="horde-icon"><?php echo $this->edit ?></li>
<?php endif ?>
<?php if ($this->unlock): ?>
    <li class="horde-icon"><?php echo $this->unlock ?></li>
<?php endif ?>
<?php if ($this->lock): ?>
    <li class="horde-icon"><?php echo $this->lock ?></li>
<?php endif ?>
<?php if ($this->remove): ?>
    <li class="horde-icon"><?php echo $this->remove ?></li>
<?php endif ?>
<?php if ($this->rename): ?>
    <li><?php echo $this->rename ?></li>
<?php endif ?>
<li><?php echo $this->backLinks ?></li>
<li><?php echo $this->likePages ?></li>
<li><?php echo $this->attachedFiles ?></li>
<?php if ($this->changes): ?>
    <li><?php echo $this->changes ?></li>
<?php endif ?>
<?php if ($this->perms): ?>
    <li><?php echo $this->perms ?></li>
<?php endif ?>
<?php if ($this->history): ?>
    <li>
      <?php echo $this->history ?>
    </li>
    <li>
      <select name="history" onchange="document.location = document.display.history[document.display.history.selectedIndex].value">
<?php foreach ($this->histories as $value => $label): ?>
        <option value="<?php echo $value ?>"><?php echo $this->h($label) ?></option>
<?php endforeach ?>
      </select>
    </li>
<?php endif ?>
  </ul>
</div>
</form>
<?php endif ?>

<div class="pagebody">
<?php if ($this->attachments): ?>
 <div class="filelist">
  <h2><?php echo _("Attachments") ?></h2>
<?php foreach ($this->attachments as $attachment): ?>
  <?php echo $attachment ?><br />
<?php endforeach ?>
 </div>
<?php endif ?>
<?php echo $this->text ?>
</div>

<?php if ($this->hasSubPages($this->name)): ?>
<div id="subpages">
  <h2><?php echo _("Related Sub Pages") ?></h2>
  <?php echo $this->subPages($this->name) ?>
</div>
<?php endif ?>

<?php if ($this->showTools): ?>
<div id="pagefooter">
 <?php echo _("Download this page as:") ?>
 <?php echo $this->downloadPlain ?>,
 <?php echo $this->downloadHtml ?>,
 <?php echo $this->downloadLatex ?>,
 <?php echo $this->downloadRest ?>
</div>
<?php endif ?>
