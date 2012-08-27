<div class="header">
 <?php echo htmlspecialchars($help_app) ?>
</div>
<br />
<div>
 <?php echo $tabs->render($vars->side_show) ?>
 <div>
  <?php echo $contents ?>
  <?php $tree->renderTree() ?>
 </div>
</div>
