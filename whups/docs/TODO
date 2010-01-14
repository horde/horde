=============================
 Whups Development TODO List
=============================

:Contact:       dev@lists.horde.org

- Ability to take/steal tickets from other users in the list UI.

- Ability to merge a ticket with another ticket (instead of marking as
  duplicate).  Histories of two tickets should be merged.

- Better permissions integration.

- Possible integration with other Horde queues (nag/turba/hermes), including
  api support between them.

- docs and help files...

- Additional "work flow" support.

- Handle attachments in Whups_Mail

- States/Priorities (or whole types?) cloning:

  | > What if we had a "clone" feature? When editing states/priorities, just
  | > being able to clone the states/priorities from another type into this
  | > type?
  |
  | I now think that this would be a good idea.  It's almost the same as
  | Gary's template patch, but it should work in a slightly different way
  | to better fit the workflow direction.
  |
  | When we have state transitions, it's more important that a type
  | doesn't change as tickets flow through it because that could cause
  | tickets to become inconsistent.  However, it's very likely that as the
  | type is being used, people see some way of making it fit the model
  | more accurately.  In that case, probably the best way to fix it is to
  | copy the existing type into a new one.  You can then modify the new
  | type however you see fit then 'publish' the type and start creating
  | tickets in it.  Published types can't be altered.
  |
  | With the type templates, you could only copy particular types into new
  | ones, instead of any type that you need to.
  |
  | However, with the templates, type were created as 'just a template',
  | ie. one that shouldn't have tickets created.  This can be quite
  | useful, but instead of using an underscore prefix to identify it, I'd
  | prefer to have a 'published' field in the table to represent types
  | that can have tickets in it.  A template could then be a normal,
  | unpublished, type.

- For project scheduling:
  http://www.joelonsoftware.com/articles/fog0000000245.html
