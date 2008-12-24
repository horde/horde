//
//  TURAnselGalleryPanelController.h
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 12/7/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "TURAnsel.h"

@interface NSObject (TURAnselGalleryPaneControllerDelegate)
-(void)TURAnselGalleryPanelDidAddGallery;
@end

@interface TURAnselGalleryPanelController : NSObject {
    // Outlets
    IBOutlet NSTextField *galleryNameTextField;
    IBOutlet NSTextField *gallerySlugTextField;
    IBOutlet NSTextField *galleryDescTextField;
    IBOutlet NSPanel *newGallerySheet;
    
    TURAnsel *anselController;
    NSWindow *controllerWindow;
    id delegate;
}

// Actions
- (IBAction)doNewGallery: (id)sender;
- (IBAction)cancelNewGallery: (id)sender;
- (id)initWithController: (TURAnsel *)theController;
- (id)initWithController: (TURAnsel *)theController withGalleryName: (NSString *)galleryName;
- (void)showSheetForWindow: (NSWindow *)theWindow;
- (void)setDelegate: (id)theDelegate;
@end
