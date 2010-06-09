<script type="text/javascript">
<?php echo $js_criteria ?>
function updateCriteria()
{
    var f = document.directory_search;
    if (!f.source.options) {
        return;
    }

    while (f.criteria.length > 0) {
        f.criteria.options[f.criteria.length - 1] = null;
    }

    var index = f.source.selectedIndex;
    if (!index) {
        index = 0;
    }

    for (var i = 0; i < criteriaOptions.length; i++) {
        if (criteriaOptions[i][0] == f.source.options[index].value) {
            for (var j = 1; j < criteriaOptions[i].length; j++) {
                f.criteria.options[f.criteria.length] = new Option(criteriaOptions[i][j][1], criteriaOptions[i][j][0]);
            }
            f.criteria.selectedIndex = 0;
            if ($('vbook-form')) {
                if (shareSources[criteriaOptions[i][0]] == true) {
                    Element.show('vbook-form');
                } else {
                    Element.hide('vbook-form');
                }
            }
            return;
        }
    }
}
Event.observe(window, 'load', updateCriteria);
</script>

<?php if ($source_count > 1): ?>
 <strong><?php echo Horde::label('source', _("From")) ?></strong>
 <select id="source" name="source" onchange="updateCriteria();">
  <?php echo $source_options ?>
 </select>
<?php endif; ?>

 <strong><?php echo Horde::label('criteria', _("Find")) ?></strong>
 <select id="criteria" name="criteria">
  <?php echo $criteria_options ?>
 </select>

 <strong><?php echo Horde::label('val', _("Matching")) ?></strong>
 <input type="text" size="30" id="val" name="val" value="<?php echo htmlspecialchars($val) ?>" />
 <input class="button" type="submit" name="search" value="<?php echo _("Search") ?>" />
