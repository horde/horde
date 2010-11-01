<div class="face-tile">
<?php
$face_url = Ansel_Faces::getLink($face);
$facename = htmlspecialchars($face['face_name']);
echo '<a href="' . $face_url . '" title="' . $facename . '">'
    . '<img src="' . $faces->getFaceUrl($face['image_id'], $face_id)
    . '" style="padding: 5px; width: 50px; height: 50px;" alt="' . $facename . '" /></a><br />';
if (isset($face['similarity'])) {
    echo '<span class="similarity-';

    // the values if similarity are fliped
    if ($face['similarity'] >= PUZZLE_CVEC_SIMILARITY_HIGH_THRESHOLD) {
        echo 'lower';
    } elseif ($face['similarity'] >= PUZZLE_CVEC_SIMILARITY_THRESHOLD) {
        echo 'low';
    } elseif ($face['similarity'] >= PUZZLE_CVEC_SIMILARITY_LOW_THRESHOLD) {
        echo 'medium';
    } else {
        echo 'high';
    }

    echo '">' . number_format((1 - $face['similarity']) * 100) . '%</span><br />';
}
if ($face['face_name']) {
    echo '<a href="' . $face_url . '" title="' . _("Find other photos this face appears in") . '">' . _("Others") . '</a><br />';
} elseif ($GLOBALS['conf']['report_content']['driver']) {
    echo Horde::url('faces/claim.php')->add('face', $face_id)->link(array('title' => _("Tell us who is in this photo")))
         . _("Claim") . '</a><br />';

    echo Horde::url('faces/report.php')->add('face', $face_id)->link(array('title' => _("Report abuse or submit a removal request")))
         . _("Report") . '</a><br />';
}
if ($conf['faces']['search'] && Horde_Util::loadExtension('libpuzzle') !== false) {
    echo Horde::url('faces/search/image_search.php')->add('face_id', $face_id)->link(array('title' => _("Find similar"))) . _("Similar faces") . '</a>';
}
?>
</div>
