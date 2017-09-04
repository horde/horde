<?php

global $expectedHeadingMatches;
$expectedHeadingMatches = array(
    0 => array(
        0 => "
! Heading 1
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        1 => "
!! Heading 2

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        2 => "
!!!Heading 3
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
            3 => "
!!- Heading 2

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        4 => "
!!+ Heading 2

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
"
    ),
    1 => array(0 => "\n", 1 => "\n", 2 => "\n", 3 => "\n", 4 => "\n"),
    2 => array(0 => "!", 1 => "!!", 2 => "!!!", 3 => "!!", 4 => "!!"),
    3 => array(0 => "", 1 => "", 2 => "", 3 => "-", 4 => "+"),
    4 => array(0 => " Heading 1", 1 => " Heading 2", 2 => "Heading 3", 3 => " Heading 2", 4 => " Heading 2"),
    5 => array(
        0 => "
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        1 => "

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        2 => "
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        3 => "

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
        4 => "

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi vitae est sit amet metus consequat scelerisque at accumsan dolor. Quisque posuere, mauris a fermentum sagittis, sem quam blandit tortor, vitae ullamcorper nulla velit placerat lacus. Nullam rutrum quam id est convallis luctus. Vivamus et urna odio. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Ut at augue eget elit feugiat pretium.
",
    ),
);

?>
