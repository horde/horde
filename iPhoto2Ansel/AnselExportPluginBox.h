/**
 * iPhoto2Ansel
 *
 * Copyright 2008-2014 Horde LLC (http://www.horde.org/)
 *
 * @license http://www.horde.org/licenses/bsd
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */

#import <Cocoa/Cocoa.h>
#import "ExportPluginProtocol.h"
#import "ExportPluginBoxProtocol.h"

@interface AnselExportPluginBox : NSBox <ExportPluginBoxProtocol> {
    IBOutlet id <ExportPluginProtocol> mPlugin;
}

- (BOOL)performKeyEquivalent: (NSEvent *)anEvent;

@end
