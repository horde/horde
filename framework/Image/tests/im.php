<?php
/**
 * Tests for the Horde_Image package. Designed to return image data in response
 * to an <img> tag on another page. Set the test parameter to one of the
 * cases below.
 *  <img src="imtest.php?driver=im&test=polaroid" />
 *
 * @package Horde_Image
 */
require_once dirname(__FILE__) . '/../../../lib/base.php';

$driver = Util::getFormData('driver', 'im');
$test = Util::getFormData('test');
switch ($test) {
case 'testInitialState':
    // Solid blue background color - basically tests initial state of the
    // Horde_Image object.
    $image = getImageObject(array('height' => '200',
                                  'width' => '200',
                                  'background' => 'blue'));
    $image->display();
    exit;
    break;

case 'testInitialStateAfterLoad':
    // Test loading an image from file directly.
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->display();
    break;

case 'testResize':
    $image = getImageObject(array('filename' => 'img2.jpg'));
    $image->resize(150, 150);
    $image->display();
    break;

case 'testPrimitivesTransparentBG':
    // Transparent PNG image with various primitives.
    $image = getImageObject(array('height' => '200',
                                  'width' => '200',
                                  'background' => 'none'));
    $image->rectangle(30, 30, 100, 60, 'black', 'yellow');
    $image->roundedRectangle(30, 30, 100, 60, 15, 'black', 'red');
    $image->circle(30, 30, 30, 'black', 'blue');
    $image->display();
    break;

case 'testTransparentPrimitivesReversed':
    // Transparent PNG image with various primitives.
    // Circle should appear *under* the rectangles...
    $image = getImageObject(array('height' => '200',
                                  'width' => '200',
                                  'background' => 'none'));
    $image->circle(30, 30, 30, 'black', 'blue');
    $image->rectangle(30, 30, 100, 60, 'black', 'yellow');
    $image->roundedRectangle(30, 30, 100, 60, 15, 'black', 'red');
    $image->display();
    break;

case 'testTransparentBGWithBorder':
    // Same as above, but with border.
     $image = getImageObject(array('height' => '200',
                                   'width' => '200',
                                   'background' => 'none'));
    $image->rectangle(30, 30, 100, 60, 'black', 'yellow');
    $image->roundedRectangle(30, 30, 100, 60, 15, 'black', 'red');
    $image->circle(30, 30, 30, 'black', 'blue');
    $image->addEffect('border', array('bordercolor' => 'blue',
                                      'borderwidth' => 1));
    $image->display();
    break;


case 'testAnnotateImage':
        $image = getImageObject(array('filename' => 'img1.jpg'));
        $image->resize(300,300);
        $image->text("Hello World", 1, 150, '', 'blue', 0, 'large');
        $image->display();
        break;

case 'testPolylineCircleLineText':
    // Various other primitives. Using different colors and strokewidths
    // to make sure that they get reset after each call - so we don't
    // inadvetantly apply a color/stroke/etc setting to a primitive
    // further down the line...
    $image = getImageObject(array('height' => '200',
                                  'width' => '200',
                                  'background' => 'none'));
    // Pie slice. Black outline, green fill
    $image->polygon(array(array('x' => 30, 'y' => 50),
                          array('x' => 40, 'y' => 60),
                          array('x' => 50, 'y' => 40)),
                   'black', 'green');

    // Yellow 'pizza slice' with blue outline
    $image->arc(50, 50, 100, 0, 70, 'blue', 'yellow');

    // Small red circle dot.
    $image->brush(80, 150, 'red', 'circle');

    // Thicker verticle green line
    $image->line(5, 30, 5, 200, 'green', 5);

    //Thinner verticle blue line
    $image->line(20, 60, 20, 200, 'blue', 2);

    // Yellow checkmark
    $image->polyline(array(array('x' => 130, 'y' => 150),
                           array('x' => 140, 'y' => 160),
                           array('x' => 150, 'y' => 140)),
                     'yellow', 4);

    $image->text('Hello World', 60, 10, 'Arial', 'black', 0, 'large');
    $image->display();
    break;

case 'testRoundCorners':
    // Tests resizing, and rounding corners with appropriate background maintained.
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->resize(150,150);
    $image->addEffect('round_corners',
                      array('border' => 2,
                            'bordercolor' => '#333',
                            'background' => 'none'));
    $image->applyEffects();
    $image->display();
    break;
case 'testRoundCornersRedBG':
    // Tests resizing, and rounding corners with appropriate background maintained.
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->resize(150,150);
    $image->addEffect('round_corners',
                      array('border' => 2,
                            'bordercolor' => '#333',
                            'background' => 'red'));
    $image->applyEffects();
    $image->display();
    break;
case 'testRoundCornersDropShadowTransparentBG':
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->resize(150,150);
    $image->addEffect('round_corners',
                      array('border' => 2,
                            'bordercolor' => '#333'));
    $image->addEffect('drop_shadow',
                      array('background' => 'none',
                            'padding' => 5,
                            'distance' => 5,
                            'fade' => 3));
    $image->display();
    break;

case 'testRoundCornersDropShadowYellowBG':
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->resize(150,150);
    $image->addEffect('round_corners',
                      array('border' => 2,
                            'bordercolor' => '#333'));
    $image->addEffect('drop_shadow',
                      array('background' => 'yellow',
                            'padding' => 5,
                            'distance' => 5,
                            'fade' => 3));
    $image->display();
    break;

case 'testBorderedDropShadowTransparentBG':
    $image = getImageObject(array('filename' => 'img1.jpg',
                                  'background' => 'none'));
    $image->resize(150,150);
    $image->addEffect('border', array('bordercolor' => '#333', 'borderwidth' => 1));
    $image->addEffect('drop_shadow',
                      array('background' => 'none',
                            'padding' => 5,
                            'distance' => '8',
                            'fade' => 2));
    $image->display();
    break;

case 'testBorderedDropShadowBlueBG':
    $image = getImageObject(array('filename' => 'img1.jpg',
                                  'background' => 'none'));
    $image->resize(150,150);
    $image->addEffect('border', array('bordercolor' => '#333', 'borderwidth' => 1));
    $image->addEffect('drop_shadow',
                      array('background' => 'blue',
                            'padding' => 5,
                            'distance' => '8',
                            'fade' => 2));
    $image->display();
    break;

case 'testPolaroidTransparentBG':
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->resize(150, 150);
    $image->addEffect('polaroid_image',
                      array('background' => 'none',
                            'padding' => 5));
    $image->display();
    break;

case 'testPolaroidBlueBG':
    $image = getImageObject(array('filename' => 'img1.jpg'));
    $image->resize(150, 150);
    $image->addEffect('polaroid_image',
                      array('background' => 'blue',
                            'padding' => 5));
    $image->display();
    break;

case 'testPlainstackTransparentBG':
    $imgs = array(getImageObject(array('filename' => 'img1.jpg')),
                  getImageObject(array('filename' => 'img2.jpg')),
                  getImageObject(array('filename' => 'img3.jpg')));
    $baseImg = getImageObject(array('width' => 1,
                                    'height' => 1,
                                    'background' => 'none'));

    $baseImg->addEffect('photo_stack',
                        array('images' => $imgs,
                              'resize_height' => 150,
                              'padding' => 0,
                              'background' => 'none',
                              'type' => 'plain'));
    $baseImg->applyEffects();
    $baseImg->display();
    break;

case 'testPlainstackBlueBG':
    $imgs = array(getImageObject(array('filename' => 'img1.jpg')),
                  getImageObject(array('filename' => 'img2.jpg')),
                  getImageObject(array('filename' => 'img3.jpg')));
    $baseImg = getImageObject(array('width' => 1,
                                    'height' => 1,
                                    'background' => 'none'));

    $baseImg->addEffect('photo_stack',
                        array('images' => $imgs,
                              'resize_height' => 150,
                              'padding' => 0,
                              'background' => 'blue',
                              'type' => 'plain'));
    $baseImg->applyEffects();
    $baseImg->display();
    break;

case 'testRoundstackTransparentBG':
        $imgs = array(getImageObject(array('filename' => 'img1.jpg')),
                  getImageObject(array('filename' => 'img2.jpg')),
                  getImageObject(array('filename' => 'img3.jpg')));
    $baseImg = getImageObject(array('width' => 1,
                                    'height' => 1,
                                    'background' => 'none'));

    $baseImg->addEffect('photo_stack',
                        array('images' => $imgs,
                              'resize_height' => 150,
                              'padding' => 0,
                              'background' => 'none',
                              'type' => 'rounded'));
    $baseImg->applyEffects();
    $baseImg->display();
    break;

case 'testRoundstackBlueBG':
        $imgs = array(getImageObject(array('filename' => 'img1.jpg')),
                  getImageObject(array('filename' => 'img2.jpg')),
                  getImageObject(array('filename' => 'img3.jpg')));
    $baseImg = getImageObject(array('width' => 1,
                                    'height' => 1,
                                    'background' => 'none'));

    $baseImg->addEffect('photo_stack',
                        array('images' => $imgs,
                              'resize_height' => 150,
                              'padding' => 0,
                              'background' => 'blue',
                              'type' => 'rounded'));
    $baseImg->applyEffects();
    $baseImg->display();
    break;

case 'testPolaroidstackTransparentBG':
    $imgs = array(getImageObject(array('filename' => 'img1.jpg')),
              getImageObject(array('filename' => 'img2.jpg')),
              getImageObject(array('filename' => 'img3.jpg')));
    $baseImg = getImageObject(array('width' => 1,
                                    'height' => 1,
                                    'background' => 'none'));

    $baseImg->addEffect('photo_stack',
                        array('images' => $imgs,
                              'resize_height' => 150,
                              'padding' => 0,
                              'background' => 'none',
                              'type' => 'polaroid'));
    $baseImg->applyEffects();
    $baseImg->display();
    break;

case 'testPolaroidstackBlueBG':
    $imgs = array(getImageObject(array('filename' => 'img1.jpg')),
              getImageObject(array('filename' => 'img2.jpg')),
              getImageObject(array('filename' => 'img3.jpg')));
    $baseImg = getImageObject(array('width' => 1,
                                    'height' => 1,
                                    'background' => 'none'));

    $baseImg->addEffect('photo_stack',
                        array('images' => $imgs,
                              'resize_height' => 150,
                              'padding' => 0,
                              'background' => 'blue',
                              'type' => 'polaroid'));
    $baseImg->applyEffects();
    $baseImg->display();
    break;
}

/**
 * Obtain a Horde_Image object
 *
 * @param array $params  Any additional parameters
 *
 * @return Horde_Image object | PEAR_Error
 */
function getImageObject($params = array())
{
    global $conf;

    $context = array('tmpdir' => Horde::getTempDir());
    if (!empty($conf['image']['convert'])) {
        $context['convert'] = $conf['image']['convert'];
    }
    $params['context'] = $context;
    return Horde_Image::factory('im', $params);
}