<?php if (!empty($criteria)): ?>
<br />
<a href="javascript:void(0)" class="bottom" onclick="saveSearch('<?php echo Horde::url('save_search.php') ?>')"><?php echo _("Save search criteria"); ?></a>
<?php
endif;
if (!empty($queries)):
?>
<br />
<br />
<table class="striped" style="width: 100%">
<h1 class="header"><?php echo _("My queries") ?></h1>
<?php
foreach ($queries as $query) {
    $delete_img = Horde::img('delete.png', _("Delete"));
    echo '<tr><td>' . Horde::link(Horde_Util::addParameter(Horde::url('search.php'), 'query', $query), '', 'bottom') . $query . '</a></td>';
    echo '<td>' . Horde::link(Horde_Util::addParameter(Horde::url('save_search.php'), array('query' => $query, 'delete' => 1))) . $delete_img . '</a></td></tr>';
}
?>
</table>
<?php endif; ?>
