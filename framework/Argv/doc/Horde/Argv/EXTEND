============
 Horde_Argv
============

----------------------
 Extending Horde_Argv
----------------------

Since the two major controlling factors in how *Horde_Argv* interprets command-line options are the action and type of each option, the most likely direction of extension is to add new actions and new types.

Adding new types
================

To add new types, you need to define your own subclass of the ``Horde_Argv_Option class``. This class has a couple of properties that define *Horde_Argv*'s types: ``$TYPES`` and ``$TYPE_CHECKER``.

``$TYPES`` is a tuple of type names; in your subclass, simply define a new tuple ``$TYPES`` that builds on the standard one.

``$TYPE_CHECKER`` is a dictionary mapping type names to type-checking functions. A type-checking function has the following signature:

::

 foo check_foo(Horde_Argv_Option $option, string $opt, string $value)

You can name it whatever you like, and make it return any type you like (e.g. the hypothetical type foo). The value returned by a type-checking function will wind up in the ``Horde_Argv_Values`` instance returned by ``Horde_Argv_Parser->parseArgs()``, or be passed to callbacks as the ``$value`` parameter.

Your type-checking function should throw ``Horde_Argv_OptionValueException`` if it encounters any problems. ``Horde_Argv_OptionValueException`` takes a single string argument, which is passed as-is to ``Horde_Argv_Parser``'s ``parserError()`` method, which in turn prepends the program name and the string ``"error:"`` and prints everything to stderr before terminating the process.

Here's a silly example that demonstrates adding an imaginary ``MyComplex`` option type to parse complex numbers on the command line.

You need to define your type-checker, since it's referred to in the ``$TYPE_CHECKER`` class attribute of your ``Horde_Argv_Option`` subclass:

::

 class MyOption extends Horde_Argv_Option
 {
     public function __construct()
     {
         $this->TYPES[] = 'complex';
         $this->TYPE_CHECKER['complex'] = 'checkComplex';
     }
 
     public function checkComplex($option, $opt, $value)
     {
         try {
             return new MyComplex(value);
         } catch (Exception $e) {
             throw new Horde_Argv_OptionValueException(
                 sprintf('option %s: invalid complex value: %s', (opt, value))
             );
         }
     }
 }

That's it! Now you can write a script that uses the new option type just like any other *Horde_Argv*-based script, except you have to instruct your ``Horde_Argv_Parser`` to use ``MyOption`` instead of ``Horde_Argv_Option``:

::

 $parser = new Horde_Argv_Parser(array('optionClass' => 'MyOption'));
 $parser->addOption('-c', array('type' => 'complex'));

Alternately, you can build your own option list and pass it to ``Horde_Argv_Parser``; if you don't use ``addOption()`` in the above way, you don't need to tell ``Horde_Argv_Parser`` which option class to use:

::

 $option_list = array(
     new MyOption(
         '-c',
         array('action' => 'store', 'type' => 'complex', 'dest' => 'c')
     )
 );
 parser = new Horde_Argv_Parser(array('optionList' => $option_list));

Adding new actions
==================

Adding new actions is a bit trickier, because you have to understand that *Horde_Argv* has a couple of classifications for actions:

:"store" actions: actions that result in *Horde_Argv* storing a value to a
                  property of the current ``Horde_Argv_Values`` instance; these options require a
                  ``dest`` attribute to be supplied to the ``Horde_Argv_Option`` constructor
:"typed" actions: actions that take a value from the command line and expect it
                  to be of a certain type; or rather, a string that can be converted to a certain
                  type. These options require a type attribute to the ``Horde_Argv_Option``
                  constructor.

These are overlapping sets: some default "store" actions are ``store``, ``store_const``, ``append``, and ``count``, while the default "typed" actions are ``store``, ``append``, and ``callback``.

When you add an action, you need to decide if it's a "store" action, a "typed" action, neither, or both. Three class properties of ``Horde_Argv_Option`` (or your ``Horde_Argv_Option`` subclass) control this:

:``$ACTIONS``:       all actions must be listed in ``$ACTIONS``
:``$STORE_ACTIONS``: "store" actions are additionally listed here
:``$TYPED_ACTIONS``: "typed" actions are additionally listed here

In order to actually implement your new action, you must override ``Horde_Argv_Option``'s ``takeAction()`` method and add a case that recognizes your action.

For example, let's add an ``extend`` action. This is similar to the standard ``append`` action, but instead of taking a single value from the command-line and appending it to an existing list, extend will take multiple values in a single comma-delimited string, and extend an existing list with them. That is, if ``"--names"`` is an ``extend`` option of type ``string``, the command line

::

 --names=foo,bar --names blah --names ding,dong

would result in a list

::

 array('foo', 'bar', 'blah', 'ding', 'dong')

Again we define a subclass of ``Horde_Argv_Option``:

::

 class MyOption extends Horde_Argv_Option
 {
     public function __construct()
     {
         $this->ACTIONS[] = 'extend';
         $this->STORE_ACTIONS[] = 'extend';
         $this->TYPED_ACTIONS[] = 'extend';
     }
 
     public function takeAction($action, $dest, $opt, $value, $values, $parser)
     {
         if ($action == 'extend') {
             $lvalue = explode(',', $value);
             $values->dest = array_merge($values->ensureValue('dest', array()),
                                         $lvalue);
         } else {
             parent::takeAction($action, $dest, $opt, $value, $values, $parser);
         }
     }
 }

Features of note:

* ``extend`` both expects a value on the command-line and stores that value somewhere, so it goes in both ``$STORE_ACTIONS`` and ``$TYPED_ACTIONS``
* ``MyOption::takeAction()`` implements just this one new action, and passes control back to ``Horde_Argv_Option::takeAction()`` for the standard *Horde_Argv* actions
* ``$values`` is an instance of the ``Horde_Argv_Values`` class, which provides the very useful ``ensureValue()`` method. ``ensureValue()`` is essentially a getter with a safety valve; it is called as

  ``$values->ensureValue($attr, $value)``
  If the ``$attr`` property of ``$values`` doesn't exist or is ``null``, then ``ensureValue()`` first sets it to ``$value``, and then returns ``$value``. This is very handy for actions like ``extend``, ``append``, and ``count``, all of which accumulate data in a variable and expect that variable to be of a certain type (an array for the first two, an integer for the latter). Using ``ensureValue()`` means that scripts using your action don't have to worry about setting a default value for the option destinations in question; they can just leave the default as ``null`` and ``ensureValue()`` will take care of getting it right when it's needed.

Other reasons to extend Horde_Argv
==================================

Adding new types and new actions are the big, obvious reasons why you might want to extend *Horde_Argv*. I can think of at least two other areas to play with.

First, the simple one: ``Horde_Argv_Parser`` tries to be helpful by calling ``exit()`` when appropriate, i.e. when there's an error on the command line or when the user requests help. In the former case, the traditional course of letting the script crash with a traceback is unacceptable; it will make users think there's a bug in your script when they make a command-line error. In the latter case, there's generally not much point in carrying on after printing a help message.

If this behaviour bothers you, it shouldn't be too hard to "fix" it. You'll have to

1. subclass ``Horde_Argv_Parser`` and override ``parserError()``
2. subclass ``Horde_Argv_Option`` and override ``takeAction()`` -- you'll need to provide your own handling of the ``help`` action that doesn't call ``exit()``

The second, much more complex, possibility is to override the command-line syntax implemented by *Horde_Argv*. In this case, you'd leave the whole machinery of option actions and types alone, but rewrite the code that processes ``argv``. You'll need to subclass ``Horde_Argv_Parser`` in any case; depending on how radical a rewrite you want, you'll probably need to override one or all of ``parseArgs()``, ``_processLongOpt()``, and ``_processShortOpts()``.

Both of these are left as an exercise for the reader. I have not tried to implement either myself, since I'm quite happy with *Horde_Argv*'s default behaviour (naturally).

Happy hacking, and don't forget: Use the Source, Luke.

