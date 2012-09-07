<div class="header">
 <?php echo htmlspecialchars($help_app) ?>
</div>
<div>
 <?php echo $tabs->render($vars->side_show) ?>
 <?php echo $contents ?>
 <?php $tree->renderTree() ?>
</div>
