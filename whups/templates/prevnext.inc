<?php
// If we have a saved list of ticket ids in the session, display the prev/next
// navigation.
if (!isset($_SESSION['whups']['tickets']) ||
    !is_array($_SESSION['whups']['tickets']) ||
    ($list_length = count($_SESSION['whups']['tickets'])) <= 1 ||
    ($this_ticket = array_search($ticket->getId(), $_SESSION['whups']['tickets'])) === false) {
    return;
}

$prevnext_links = array();
if ($this_ticket != 0) {
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket',
                      $_SESSION['whups']['tickets'][0]))
        . htmlspecialchars(_("<<First")) . '</a>';
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket',
                      $_SESSION['whups']['tickets'][$this_ticket - 1]))
        . htmlspecialchars(_("<Prev")) . '</a>';
}
if ($this_ticket + 1 != $list_length) {
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket',
                      $_SESSION['whups']['tickets'][$this_ticket + 1]))
        . htmlspecialchars(_("Next>")) . '</a>';
    $prevnext_links[] = Horde::link(
        Whups::urlFor('ticket',
                      $_SESSION['whups']['tickets'][$list_length - 1]))
        . htmlspecialchars(_("Last>>")) . '</a>';
}
$label = _("Re_turn to Search Results");
$ak = Horde::getAccessKey($label);
$label = Horde::highlightAccessKey($label, $ak);
?>
<div id="searchnav">
 <p>
  <strong><?php printf(_("Search Results: %s of %s"), $this_ticket + 1, count($_SESSION['whups']['tickets'])) ?></strong>
  <small>
   [ <?php echo implode(' ', $prevnext_links) ?> ]
   <?php if (!empty($_SESSION['whups']['last_search'])): ?>
   [ <?php echo Horde::link(Horde_Util::addParameter($_SESSION['whups']['last_search'], 'haveSearch', true), '', '', '', '', '', $ak) . $label ?></a> ]
   <?php endif; ?>
  </small>
 </p>
</div>
