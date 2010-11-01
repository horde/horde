/*
     File:       ExportPluginBoxProtocol.h

     Contains:   iPhoto Plug-ins interfaces: Export plugin box expected format and methods

     Version:    Technology: iPhoto
                 Release:    1.0

     Copyright:  © 2002-2007 by Apple Inc. All rights reserved.

     Bugs?:      For bug reports, consult the following page on
                 the World Wide Web:

                     http://developer.apple.com/bugreporter/
*/

#import <Cocoa/Cocoa.h>

//------------------------------------------------------------------------------
@protocol ExportPluginBoxProtocol

//------------------------------------------------------------------------------
// Public methods
//------------------------------------------------------------------------------

// Used to respond to the enter key
- (BOOL)performKeyEquivalent:(NSEvent *)anEvent;

//------------------------------------------------------------------------------
@end
