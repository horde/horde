<?php
/**
 * Test harness for generating the test images for Horde_Image tests
 */

require_once dirname(__FILE__) . '/../../../horde/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

$allTests = array(
    'testInitialState' => 'Test initial state. Solid blue square',
    'testPrimitivesTransparentBG' => 'Transparent background, various primitives. Cirlce should be above the rectangles.',
    'testTransparentBGWithBorder' => 'Test transparent background with border preserving transparency.',
    'testTransparentPrimitivesReversed' => 'Test ordering of primitives. This should show the circle *below* the rectangles.',
    'testAnnotateImage' => 'Annotate Image with Hello World in center left',
    'testPolylineCircleLineText' => 'various other primitives, as well as state of stroke color, width etc...',
    'testRoundCorners' => 'Rounded corners with transparent background.',
    'testRoundCornersRedBG' => 'Rounded corners with red background.',
    'testRoundCornersDropShadowTransparentBG' => 'Rounded corners with a drop shadow on a transparent background.',
    'testRoundCornersDropShadowYellowBG' => 'Rounded corners, with a drop shadow on a yellow background',
    'testBorderedDropShadowTransparentBG' => 'Thumbnail with border and drop shadow over a transparent background.',
    'testBorderedDropShadowBlueBG' => 'Thumbnail with border, drop shadow over a blue background.',
    'testPolaroidTransparentBG' => 'Polaroid effect with transparent background.',
    'testPolaroidBlueBG' => 'Polaroid effect with blue background.',
    'testPlainstackTransparentBG' => 'Thumbnail stack on transparent background.',
    'testPlainstackBlueBG' => 'Thumbnail stack on a blue background.',
    'testRoundstackTransparentBG' => 'Thumbnail stack with rounded borders on a transparent background',
    'testRoundstackBlueBG' => 'Thumbnail stack, rounded corners on a blue background',
    'testPolaroidstackTransparentBG' => 'Polaroid stack on a transparent background.',
    'testPolaroidstackBlueBG' => 'Polaroid stack on a blue background',
    //'testInitialStateAfterLoad' => 'Initial state after loading an existing image.',
    'testResize' => 'Test resize method.',
    'multipage' => 'Test Multipage tiffs',
);
?>
<html>
 <head>
  <title>Horde_Image Tests</title>
 </head>
 <body style="background-color:gray">
<table width="50%">
 <thead><td>Effect</td><td>Im</td><td>Imagick</td></thead>
<?php
foreach ($allTests as $name => $description) {
    echo '<tr><td text-align="top">' . $description . '</td><td>' . Horde::img('im.php?test=' . $name, '', '', '') . '</td>' . '</tr>';
      //'<td text-align="top">' . Horde::img('im.php?test=' . $name . '&driver=Imagick', '', '', '') . '</td></tr>';
}
echo '</table>';
?></body></html>
