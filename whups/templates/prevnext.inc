<?php
// If we have a saved list of ticket ids in the session, display the prev/next
// navigation.
$whups_tickets = $GLOBALS['session']->get('whups', 'tickets', Horde_Session::TYPE_ARRAY);

if (($list_length = count($whups_tickets)) <= 1 ||
    ($this_ticket = array_search($ticket->getId(), $whups_tickets)) === false) {
    return;
}

$prevnext_links = array();
if ($this_ticket != 0) {
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket', $whups_tickets[0]))
        . htmlspecialchars(_("<<First")) . '</a>';
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket', $whups_tickets[$this_ticket - 1]))
        . htmlspecialchars(_("<Prev")) . '</a>';
}
if ($this_ticket + 1 != $list_length) {
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket', $whups_tickets[$this_ticket + 1]))
        . htmlspecialchars(_("Next>")) . '</a>';
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket', $whups_tickets[$list_length - 1]))
        . htmlspecialchars(_("Last>>")) . '</a>';
}
$label = _("Re_turn to Search Results");
$ak = Horde::getAccessKey($label);
$label = Horde::highlightAccessKey($label, $ak);
?>
<div id="searchnav">
 <p>
  <strong><?php printf(_("Search Results: %s of %s"), $this_ticket + 1, $list_length) ?></strong>
  <small>
   [ <?php echo implode(' ', $prevnext_links) ?> ]
   <?php if ($ls = $GLOBALS['session']->get('whups', 'last_search')): ?>
   [ <?php echo Horde::link(Horde_Util::addParameter($ls, 'haveSearch', true), '', '', '', '', '', $ak) . $label ?></a> ]
   <?php endif; ?>
  </small>
 </p>
</div>
