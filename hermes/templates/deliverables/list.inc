<br />
<table width="100%">
<tr><td class="header"><?php
    printf(_("%s Deliverables"), $clientname);

    $newurl = Horde::url('deliverables.php');
    $newurl = Horde_Util::addParameter($newurl, array('new' => 1, 'client_id' => $vars->get('client_id')));

?> <a href="<?php echo $newurl ?>"><?php
    echo Horde::img('newdeliverable.png', _("New Top-level Deliverable"));
?></a></td></tr>
<tr><td><?php $tree->renderTree() ?></td></tr>
</table>
