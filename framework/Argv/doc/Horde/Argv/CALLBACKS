============
 Horde_Argv
============

------------------
 Option Callbacks
------------------

When *Horde_Argv*'s built-in actions and types aren't quite enough for your needs, you have two choices: extend *Horde_Argv* or define a callback option. Extending *Horde_Argv* is more general, but overkill for a lot of simple cases. Quite often a simple callback is all you need.

You define a callback in two steps:

* define the option itself using the callback action
* write the callback; this is a function (or method) that takes at least four arguments, as described below

Defining a callback option
==========================

As always, the easiest way to define a callback option is by using the ``addOption()`` method of your ``Horde_Argv_Parser`` object. The only option attribute you must specify is callback, the function to call:

::

 $parser->addOption('-c', array('action' => 'callback', 'callback' => 'my_callback'));

Note that you supply a ``callable`` here -- so you must have already defined a function ``my_callback()`` when you define the ``callback`` option. In this simple case, *Horde_Argv* knows nothing about the arguments the "-c" option expects to take. Usually, this means that the option doesn't take any arguments -- the mere presence of "-c" on the command-line is all it needs to know. In some circumstances, though, you might want your callback to consume an arbitrary number of command-line arguments. This is where writing callbacks gets tricky; it's covered later in this document.

*Horde_Argv* always passes four particular arguments to your callback, and it will only pass additional arguments if you specify them via ``callback_args`` and ``callback_kwargs``. Thus, the minimal callback function signature is:

::

 function my_callback($option, $opt, $value, $parser)

The four arguments to a callback are described below.

There are several other option attributes that you can supply when you define an option attribute:

:``type``:            has its usual meaning: as with the ``store`` or ``append``
                      actions, it instructs *Horde_Argv* to consume one argument and convert it to
                      ``type``. Rather than storing the converted value(s) anywhere, though,
                      *Horde_Argv* passes it to your callback function.
:``nargs``:           also has its usual meaning: if it is supplied and > 1,
                      *Horde_Argv* will consume ``nargs`` arguments, each of which must be convertible
                      to ``type``. It then passes an array of converted values to your callback.
:``callback_args``:   an array of extra positional arguments to pass to the
                      callback
:``callback_kwargs``: a hash of extra keyword arguments to pass to the callback

How callbacks are called
========================

All callbacks are called as follows:

::

 func(Horde_Argv_Option $option,
      string $opt,
      mixed $value,
      Horde_Argv_Parser $parser,
      array $args,
      array $kwargs)

where

:``$option``: is the ``Horde_Argv_Option`` instance that's calling the callback
:``$opt``:    is the option string seen on the command-line that's triggering the
              callback. (If an abbreviated long option was used, ``$opt`` will be the full,
              canonical option string -- e.g. if the user puts "--foo" on the command-line as
              an abbreviation for "--foobar", then ``$opt`` will be "--foobar".)
:``$value``:  is the argument to this option seen on the command-line.
              *Horde_Argv* will only expect an argument if ``type`` is set; the type of
              ``$value`` will be the type implied by the option's type (see "Option types"
              below). If ``type`` for this option is ``null`` (no argument expected), then
              ``$value`` will be ``null``. If ``nargs`` > 1, ``$value`` will be an array of
              values of the appropriate type.
:``$parser``: is the ``Horde_Argv_Parser`` instance driving the whole thing,
              mainly useful because you can access some other interesting data through it, as
              instance attributes:

    : ``$parser->largs`` : the current list of leftover arguments, ie. arguments that have been consumed but are neither options nor option arguments. Feel free to modify ``$parser->largs``, e.g. by adding more arguments to it. (This list will become ``$args``, the second return value of ``parseArgs()``.)
    : ``$parser->rargs`` : the current list of remaining arguments, ie. with ``$opt`` and ``$value`` (if applicable) removed, and only the arguments following them still there. Feel free to modify ``$parser->rargs``, e.g. by consuming more arguments.
    : ``$parser->values`` : the object where option values are by default stored (an instance of ``Horde_Argv_Values``). This lets callbacks use the same mechanism as the rest of *Horde_Argv* for storing option values; you don't need to mess around with globals or closures. You can also access or modify the value(s) of any options already encountered on the command-line.
:``$args``:   is a tuple of arbitrary positional arguments supplied via the
              ``callback_args`` option attribute.
:``$kwargs``: is a dictionary of arbitrary keyword arguments supplied via
              ``callback_kwargs``.

Error handling
==============

The callback function should throw ``Horde_Argv_OptionValueException`` if there are any problems with the option or its argument(s). *Horde_Argv* catches this and terminates the program, printing the error message you supply to stderr. Your message should be clear, concise, accurate, and mention the option at fault. Otherwise, the user will have a hard time figuring out what he did wrong.

Examples part 1: no arguments
=============================

Here's an example of a callback option that takes no arguments, and simply records that the option was seen:

::

 function record_foo_seen($option, $opt, $value, $parser)
 {
     $parser->saw_foo = true;
 }
 
 $parser->addOption(
     '--foo',
     arry('action' => 'callback', 'callback' => 'record_foo_seen')
 );

Of course, you could do that with the ``store_true`` action. Here's a slightly more interesting example: record the fact that "-a" is seen, but blow up if it comes after "-b" in the command-line.

::

 $check_order = function($option, $opt, $value, $parser)
 {
     if ($parser->values->b) {
         throw new Horde_Argv_OptionValueException("can't use -a after -b");
     }
     $parser->values->a = 1;
 }
 [...]
 $parser->addOption(
     '-a',
     array('action' => 'callback', 'callback' => $check_order)
 );
 $parser->addOption('-b', array('action' => 'store_true', 'dest' => 'b'));

If you want to re-use this callback for several similar options (set a flag, but blow up if "-b" has already been seen), it needs a bit of work: the error message and the flag that it sets must be generalized.

::

 function check_order($option, $opt, $value, $parser)
 {
     if ($parser->values->b) {
         throw new Horde_Argv_OptionValueException(sprintf("can't use %s after -b", $opt));
     }
     $parser->values->{$option->dest} = 1;
 }
 [...]
 $parser->addOption(
     '-a',
     array('action' => 'callback', 'callback' => 'check_order', 'dest' => 'a')
 );
 $parser->addOption(
     '-b',
     array('action' => 'store_true', 'dest' => 'b')
 );
 $parser->addOption(
     '-c',
     array('action' => 'callback', 'callback' => 'check_order', 'dest' => 'c')
 );

Of course, you could put any condition in there -- you're not limited to checking the values of already-defined options. For example, if you have options that should not be called when the moon is full, all you have to do is this:

::

 function check_moon($option, $opt, $value, $parser)
 {
     if (is_moon_full()) {
         throw new Horde_Argv_OptionValueException(sprintf('%s option invalid when moon is full', $opt));
     }
     $parser->values->{$option->dest} = 1;
 }
 [...]
 $parser->addOption(
     '--foo',
     array('action' => 'callback', 'callback' => 'check_moon', 'dest' => 'foo')
 );

(The definition of ``is_moon_full()`` is left as an exercise for the reader.)

Examples part 2: fixed arguments
================================

Things get slightly more interesting when you define callback options that take a fixed number of arguments. Specifying that a callback option takes arguments is similar to defining a ``store`` or ``append`` option: if you define ``type``, then the option takes one argument that must be convertible to that type; if you further define ``nargs``, then the option takes ``nargs`` arguments.

Here's an example that just emulates the standard ``store`` action:

::

 function store_value($option, $opt, $value, $parser)
 {
     $parser->values->{$option->dest} = $value;
 }
 [...]
 $parser->addOption(
     '--foo',
     array('action' => 'callback', 'callback' => 'store_value',
           'type' => 'int', 'nargs' => 3, 'dest' => 'foo')
 );

Note that *Horde_Argv* takes care of consuming 3 arguments and converting them to integers for you; all you have to do is store them. (Or whatever: obviously you don't need a callback for this example. Use your imagination!)

Examples part 3: variable arguments
===================================

Things get hairy when you want an option to take a variable number of arguments. For this case, you must write a callback, as *Horde_Argv* doesn't provide any built-in capabilities for it. And you have to deal with certain intricacies of conventional Unix command-line parsing that *Horde_Argv* normally handles for you. In particular, callbacks have to worry about bare "--" and "-" arguments; the convention is:

* bare "--", if not the argument to some option, causes command-line processing to halt and the "--" itself is lost
* bare "-" similarly causes command-line processing to halt, but the "-" itself is kept
* either "--" or "-" can be option arguments

If you want an option that takes a variable number of arguments, there are several subtle, tricky issues to worry about. The exact implementation you choose will be based on which trade-offs you're willing to make for your application (which is why *Horde_Argv* doesn't support this sort of thing directly).

Nevertheless, here's a stab at a callback for an option with variable arguments:

::

 function vararg_callback($option, $opt, $value, $parser)
 {
     $done = 0;
     $value = array();
     $rargs = $parser->rargs;
     while ($rargs) {
         $arg = $rargs[0];
 
         // Stop if we hit an $arg like '--foo', '-a', '-fx', '--file=f',
         // etc.  Note that this also stops on '-3' or '-3.0', so if
         // your option takes numeric values, you will need to handle
         // this.
         if ((substr($arg, 0, 2) == '--' && strlen($arg) > 2) ||
             ($arg[0] == '-' && strlen($arg) > 1 && $arg[1] != '-')) {
             break;
         } else {
             $value[] = $arg;
         }
         array_shift($rargs);
     }
 
     $parser->values->{$option->dest} = $value;
 }
 
 [...]
 $parser->addOption(
     '-c', '--callback',
     array('action' => 'callback', 'callback' => 'vararg_callback')
 );

The main weakness with this particular implementation is that negative numbers in the arguments following "-c" will be interpreted as further options, rather than as arguments to "-c". Fixing this is left as an exercise for the reader.

