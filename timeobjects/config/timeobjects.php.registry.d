<?php
// Edit to suit your needs. I use kronolith's fileroot here to start for
// simplicity for now since they are both in hatchery.
$this->applications['timeobjects'] = array(
    'fileroot' => $this->applications['kronolith']['fileroot'] . '/../timeobjects',
    'status' => 'hidden',
    'name' => _("Time Objects"),
    'provides' => 'timeobjects'
);