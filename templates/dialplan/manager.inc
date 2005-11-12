<div class="header">
    <div class="contextHeader">Context: <?php echo $context; ?></div>
</div>

<?php
$dpgui->renderNavTree();
$dpgui->renderAppList();
$dpgui->renderExtensions();
?>