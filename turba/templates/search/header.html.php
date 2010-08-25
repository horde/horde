<div class="text" style="padding:1em">
<form name="directory_search" action="<?php echo Horde::applicationUrl('search.php') ?>" method="post" onsubmit="RedBox.loading(); return true;">
<?php echo Horde_Util::formInput() ?>
<?php if ($this->uniqueSource): ?>
<input type="hidden" id="turbaSearchSource" name="source" value="<?php echo $this->uniqueSource ?>" />
<?php endif; ?>
