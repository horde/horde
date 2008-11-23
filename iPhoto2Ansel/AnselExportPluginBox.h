//
//  AnselExportPluginBox.h
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 10/23/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "ExportPluginProtocol.h"
#import "ExportPluginBoxProtocol.h"

@interface AnselExportPluginBox : NSBox <ExportPluginBoxProtocol> {
    IBOutlet id <ExportPluginProtocol> mPlugin;
}

- (BOOL)performKeyEquivalent: (NSEvent *)anEvent;

@end
