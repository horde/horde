<div class="text" style="padding:1em">
 <div id="advancedtoggle" style="display:none">
  <a href="#"><?php echo _("Search Criteria Hidden - Click to Display") ?></a>
 </div>
 <form name="directory_search" action="<?php echo Horde::url('search.php') ?>" method="post" onsubmit="RedBox.loading(); return true;" style="display:none">
  <?php echo Horde_Util::formInput() ?>
<?php if ($this->uniqueSource): ?>
  <input type="hidden" id="turbaSearchSource" name="source" value="<?php echo $this->uniqueSource ?>" />
<?php endif; ?>
