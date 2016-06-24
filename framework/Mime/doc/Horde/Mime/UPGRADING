======================
 Upgrading Horde_Mime
======================

:Contact: dev@lists.horde.org

.. contents:: Contents
.. section-numbering::


This lists the API changes between releases of the package.


Upgrading to 2.10
=================

  - Horde_Mime_Part

    - isAttachment()

      This method has been added.


Upgrading to 2.9
================

  - Horde_Mime_Part_Iterator

    This class has been added.


Upgrading to 2.8
================

  - Horde_Mime_Part

    - $encodingTypes
    - $mimeTypes

      These static properties are deprecated.

    - Iteration

      The object can now be iterated through to access the subparts.
      partIterator() can be used to obtain a RecursiveIteratorIterator that
      will iterator through all descendants.
      The $parent property has been added; it will be set, and is only
      guaranteed to be accurate, during iteration.

    - addPart()

      This method is deprecated. Use array access to add a subpart (e.g.
      $part[] = $new_part).

    - alterPart()

      This method is deprecated. Use array access (with the MIME ID as the
      key) to alter a subpart.

    - clearContentTypeParameter()

      This method is deprecated. Use Horde_Mime_Part#setContentTypeParameter()
      instead with null as the second argument to delete a Content-Type
      parameter.

    - contentTypeMap()

      This method is deprecated. Use partIterator() to recursively iterate
      through the parts instead.

    - getPart()

      This method is deprecated. Use array access (with the MIME ID as the
      key) to obtain a subpart.

    - setContentTypeParameter()

      Passing null as the second argument will cause the Content-Type
      parameter identified by the first argument to be deleted.

    - setDispositionParameter()

      Passing null as the second argument will cause the Content-Disposition
      parameter identified by the first argument to be deleted.

    - setDescription()

      Passing null as the argument will cause the description to be deleted.

    - removePart()

      This method is deprecated. Use array access (with the MIME ID as the
      key) to remove a subpart.

  - Horde_Mime_Headers_ContentId
  - Horde_Mime_Headers_ContentLanguage
  - Horde_Mime_Headers_ContentParam_ContentDisposition
  - Horde_Mime_Headers_ContentParam_ContentType
  - Horde_Mime_Headers_ContentTransferEncoding
  - Horde_Mime_Headers_Mime

    These classes have been added.

  - Horde_Mime_Headers_Element

    - isDefault()

      This method has been added.

  - Horde_Mime_Headers_Extension_Mime

    This interface has been added.

  - Horde_Mime_Headers_ContentParam

    - setContentParamValue()

      This method has been added.



Upgrading to 2.7
================

  - Horde_Mime_Headers_ContentDescription

    This class has been added.


Upgrading to 2.6
================

  - Horde_Mime_Filter_Encoding

    This class has been added.

  - Horde_Mime

    - $decodeWindows1252

      This property now defaults to true. (HTML 5 demands that ISO-8859-1 be
      treated as Windows-1252, so this is matching conventional usage.)


Upgrading to 2.5
================

  - Horde_Mime

    - $brokenRFC2231

      This static property is deprecated. Use the 'broken_rfc2231' parameter
      to Horde_Mime_Headers_ContentParam#encode() instead.

    - decodeParam()

      This method is deprecated. Use Horde_Mime_Headers_ContentParam#decode()
      instead.

    - encodeParam()

      This method is deprecated. Use Horde_Mime_Headers_ContentParam#encode()
      instead.

    - generateMessageId()

      This method is deprecated. Use Horde_Mime_Headers_MessageId::create()
      instead.

    - is8bit()

      The 2nd parameter ($charset) is no longer needed/used.

    - isChild()

      This method is deprecated. Use Horde_Mime_Id#isChild() instead.

    - mimeIdArithmetic()

      This method is deprecated. Use Horde_Mime_Id#idArithmetic() instead.

    - quotedPrintableEncode()

      This method is deprecated. Use PHP's quoted_printable_encode() method
      instead (or, if you need to control EOL and/or wrapping length, use
      Horde_Mime_QuotedPrintable::encode()).

    - uudecode()

      This method is deprecated. Use the Horde_Mime_Uudecode() class instead.

  - Horde_Mime_Headers

    Headers are now internally stored in a list of Horde_Mime_Headers_Element
    objects.

    - addHeader()

      Deprecate the 'decode' and 'params' optional parameters.
      To indicate distinct value and parameter data, use the
      Horde_Mime_Headers_ContentParam object to add the data.
      MIME decoding is now done automatically, based on the header name.

    - addHeaderOb()

      This method has been added.

    - addMessageIdHeader()

      This method is deprecated. Add a Horde_Mime_Headers_MessageId object via
      Horde_Mime_Headers#addHeaderOb() instead.

    - addReceivedHeader()

      This method is deprecated. Create an object using
      Horde_Mime_Headers_Received::createHordeHop() and add to the headers
      object via Horde_Mime_Headers#addHeaderOb().

    - addUserAgentHeader()

      This method is deprecated. Add a Horde_Mime_Headers_UserAgent object via
      Horde_Mime_Headers#addHeaderOb() instead.

    - getEOL()

      This method is deprecated.

    - getUserAgent()

      This method is deprecated. Get the default user agent via
      strval(Horde_Mime_Headers_UserAgent::create()) instead.

    - listHeadersExist()

      This method is deprecated. Use Horde_ListHeaders#listHeadersExist()
      instead.

    - getOb()

      This method is deprecated. Directly obtain the header element that
      implements Horde_Mime_Headers_Element_Address and call
      getAddressList() on it.

    - getString()

      This method is deprecated. Directly obtain the header element and query
      its 'name' property instead.

    - replaceHeader()

      This method is deprecated. Use Horde_Mime_Headers#removeHeader()
      followed by Horde_Mime_Headers#addHeader[Ob]() instead.

    - setEOL()

      This method is deprecated. EOLs are only needed for
      Horde_Mime_Headers#toString(), where the 'canonical' parameter already
      exists for the same purpose.

    - setUserAgent()

      This method is deprecated. Set the user agent via
      Horde_Mime_Headers#addHeaderOb() instead.

  - Horde_Mime_ContentParam_Decode
  - Horde_Mime_Id
  - Horde_Mime_QuotedPrintable
  - Horde_Mime_Uudecode

    These classes were added.

  - Horde_Mime_Part

    - send()

      Added the 'broken_rfc2231' option.


Upgrading to 2.4
================

  - Horde_Mime_Mail

    - getRaw()

      This method was added.


Upgrading to 2.3
================

  - Horde_Mime_Headers

    - parseHeaders()

      The $text parameter now accepts both resources and Horde_Stream objects.


Upgrading to 2.2
================

  - Horde_Mime

    - parseMessage()

      Added the 'no_body' parameter.


Upgrading to 2.1
================

  - Horde_Mime

    - encodeParam()

      The 'escape' option has been removed.
      The MIME_PARAM_QUOTED constant has been added.

  - Horde_Mime_Related

    - cidReplace()

      The $text parameter can now be a Horde_Domhtml object.


Upgrading to 2.0
================

  - Horde_Mime

    - decode()

      Removed the second parameter ($to_charset). Output is now in UTF-8.

    - decodeAddrString()

      This method has been removed.
      Equivalent functionality can now be found in the Horde_Mail package
      (version 2.0.0+).

    - decodeParam()

      Removed the third parameter ($charset). Output is now in UTF-8.

    - encode()

      The first parameter ($text) now requires a UTF-8 string.
      The second parameter ($charset) is now optional and indicates the
      charset to MIME encode to.

    - encodeAddress()

      This method has been removed.
      Equivalent functionality can now be found in the Horde_Mail package
      (version 2.0.0+).

    - encodeParam()

      The third parameter ($charset) has been removed and moved to the options.
      By default, the string is encoded in UTF-8.

  - Horde_Mime_Address

    This class has been removed. Equivalent functionality can now be found
    in the Horde_Mail package (version 2.0.0+).

  - Horde_Mime_Headers

    - addHeader()

      Removed the 'charset' and 'decode' parameters.
      Added the 'sanity_check' parameter.

    - getOb()

      Now returns null if the header does not exist.

    - replaceHeader()

      Removed the 'charset' and 'decode' parameters.
      Added the 'sanity_check' parameter.

    - setValue()
    - sanityCheck()

      These methods have been removed.
