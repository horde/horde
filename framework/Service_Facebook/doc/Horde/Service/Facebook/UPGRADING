=================================
 Upgrading Horde_Service_Facebook
=================================

:Contact: dev@lists.horde.org

.. contents:: Contents
.. section-numbering::


This lists the API changes between releases of the package.


Upgrading to 2.0.0
==================

Batch processing has been removed until it can be ported to the Graph API.

  - Horde_Service_Facebook

    - callUploadMethod()

      This method has been removed. You should use the upload method of the
      request object directly.

  - Horde_Service_Facebook_Events

    - get()

      This method now takes a parameter array in place of numerous default
      parameters.

    - getMembers()

      This method now returns an array of objects representing all invited users
      in place of separate arrays for each rsvp_status type.

    - create()

      This method now requires a $uid parameter.

    - edit()

      This method has been removed since Facebook no longer allows editng user
      events via the API.

  - Horde_Service_Facebook_Notes

    - delete()

      This method no longer takes a $uid argument.

    - edit()

      This method has been removed.

  - Horde_Service_Facebook_Notifications

    - send(), sendEmail()

      These methods have been removed since they are no longer supported by
      Facebook. The get() method is still supported, but is not yet available
      in the Graph API so it is left untouched.

  - Horde_Service_Facebook_Photos

    - upload()

      This method now takes a parameter array in place of multiple, optional
      parameters.

  - Horde_Service_Facebook_Streams

    - get()

      This method has been removed and replaced by both getWall() and getStream().

    - getComments()

      This method has been removed. Comments are now returned in getWall() and
      getStream() calls.

    - publish()

      This method has been removed. You should use e.g., post() instead.

    - remove()

      This method was renamed to delete() to match the GraphAPI method name.

    - addComment(), removeComment(), addLike(), removeLike()

      The $uid parameter has been removed since it is no longer possible to
      post as another user from the GraphAPI.

    - getFilters()

      The $uid paramter is now required.

  - Horde_Service_Facebook_Users

    - hasAppPermission()

      This method has been removed. Use the new getAppPermissions() instead.
      This returns an array of all application permissions.

    - isAppUser()

      This method has been removed. Use the new getAppPermissions() method
      instead.

    - getStandardInfo()

      This method has been removed.

    - getInfo()

      This method now only request user info for a single user at a time.

    - isVerified()

      This method has been removed. Use getInfo() instead.

    - setStatus()

      This method was removed. Use the Horde_Service_Facebook_Streams::post
      method instead.

    - getStatus()

      This method was removed.
