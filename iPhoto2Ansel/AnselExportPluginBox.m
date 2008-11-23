//
//  AnselExportPluginBox.m
//  iPhoto2Ansel
//
//  Generic PluginBox handles Enter key presses to initiate the
//  export.
//
//  Created by Michael Rubinsky on 10/23/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import "AnselExportPluginBox.h"

@implementation AnselExportPluginBox


-(BOOL)performKeyEquivalent:(NSEvent *)anEvent
{
    NSString *keyString = [anEvent charactersIgnoringModifiers];
    unichar keyChar = [keyString characterAtIndex:0];
    
    switch (keyChar)
    {
        case NSFormFeedCharacter:
        case NSNewlineCharacter:
        case NSCarriageReturnCharacter:
        case NSEnterCharacter:
        {
            [mPlugin clickExport];
            return(YES);
        }
        default:
            break;
    }
    
    return([super performKeyEquivalent:anEvent]);
}
@end
