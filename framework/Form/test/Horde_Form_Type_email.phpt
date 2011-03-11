--TEST--
Horde_Form_Type_email tests
--FILE--
<?php

require 'Horde/Autoloader.php';
require dirname(__FILE__) . '/../Form.php';

$vars = Horde_Variables::getDefaultVariables();
$type = new Horde_Form_Type_email();
$var = new Horde_Form_Variable('email add', 'email', $type, true);

function test($email)
{
    global $type, $var, $vars;
    $valid = $type->isValid($var, $vars, $email, $message);
    echo $valid ? 'Yes' : 'No: ' . $message;
    echo "\n";
}

test('cal@iamcalx.com');
test('cal+henderson@iamcalx.com');
test('cal henderson@iamcalx.com');
test('"cal henderson"@iamcalx.com');
test('cal@iamcalx');
test('cal@iamcalx com');
test('cal@hello world.com');
test('cal@[hello].com');
test('cal@[hello world].com');
test('cal@[hello\\ world].com');
test('cal@[hello.com]');
test('cal@[hello world.com]');
test('cal@[hello\\ world.com]');
test('abcdefghijklmnopqrstuvwxyz@abcdefghijklmnopqrstuvwxyz');

test('woo\\ yay@example.com');
test('woo\\@yay@example.com');
test('woo\\.yay@example.com');

test('"woo yay"@example.com');
test('"woo@yay"@example.com');
test('"woo.yay"@example.com');
test('"woo\\"yay"@test.com');

test('webstaff@redcross.org');

test('');
test(',');
test(',,,,');
test('chuck@horde.org,');
test('chuck@horde.org,,');
test('cal@iamcalx.com, foo@example.com');
test(',chuck@horde.org,');

$type->_allow_multi = true;
test('');
test(',');
test(',,,,');
test('chuck@horde.org,');
test('chuck@horde.org,,');
test('cal@iamcalx.com, foo@example.com');
test(',chuck@horde.org,');

?>
--EXPECT--
Yes
Yes
No: "cal henderson@iamcalx.com" is not a valid email address.
Yes
No: "cal@iamcalx" is not a valid email address.
No: "cal@iamcalx com" is not a valid email address.
No: "cal@hello world.com" is not a valid email address.
No: "cal@[hello].com" is not a valid email address.
No: "cal@[hello world].com" is not a valid email address.
No: "cal@[hello\ world].com" is not a valid email address.
No: "cal@[hello.com]" is not a valid email address.
No: "cal@[hello world.com]" is not a valid email address.
No: "cal@[hello\ world.com]" is not a valid email address.
No: "abcdefghijklmnopqrstuvwxyz@abcdefghijklmnopqrstuvwxyz" is not a valid email address.
No: "woo\ yay@example.com" is not a valid email address.
No: "woo\@yay@example.com" is not a valid email address.
No: "woo\.yay@example.com" is not a valid email address.
Yes
Yes
Yes
Yes
Yes
No: You must enter an email address.
No: You must enter an email address.
No: Only one email address is allowed.
Yes
No: Only one email address is allowed.
No: Only one email address is allowed.
No: Only one email address is allowed.
No: You must enter at least one email address.
No: You must enter at least one email address.
No: You must enter at least one email address.
Yes
Yes
Yes
Yes
