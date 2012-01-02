/**
 * AnselExportPluginBox.m
 * iPhoto2Ansel
 *
 * Copyright 2008-2011 Horde LLC (http://www.horde.org/)
 *
 * @license http://www.horde.org/licenses/bsd
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
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
