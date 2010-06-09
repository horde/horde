<script type="text/javascript">
TurbaSearch.criteria = <?php echo json_encode($criteria) ?>;
TurbaSearch.shareSources = <?php echo json_encode($shareSources) ?>;
</script>

<?php if ($source_count > 1): ?>
 <strong><?php echo Horde::label('source', _("From")) ?></strong>
 <select id="turbaSearchSource" name="source" onchange="TurbaSearch.updateCriteria();">
  <?php echo $source_options ?>
 </select>
<?php endif; ?>

 <strong><?php echo Horde::label('criteria', _("Find")) ?></strong>
 <select id="turbaSearchCriteria" name="criteria">
  <?php echo $criteria_options ?>
 </select>

 <strong><?php echo Horde::label('val', _("Matching")) ?></strong>
 <input type="text" size="30" id="val" name="val" value="<?php echo htmlspecialchars($val) ?>" />
 <input class="button" type="submit" name="search" value="<?php echo _("Search") ?>" />
