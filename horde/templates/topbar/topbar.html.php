<?php echo $this->renderPartial('menubar') ?>
<?php echo $this->renderPartial('subbar') ?>
<div id="horde-body"<?php if (!$this->sidebar) echo ' class="horde-no-sidebar"' ?>>
<div id="horde-contentwrapper">
<div id="horde-content"<?php if ($this->sidebar) echo ' style="margin-left:' . $this->sidebarWidth . 'px"' ?>>
