/**
 * TURAnselGalleryPanelController
 *
 * Copyright 2009-2011 The Horde Project (http://www.horde.org)
 *
 * @license http://opensource.org/licenses/bsd-license.php
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 */
#import <Cocoa/Cocoa.h>
#import "TURAnselKit.h"

@interface NSObject (TURAnselGalleryPaneControllerDelegate)
-(void)TURAnselGalleryPanelDidAddGallery;
@end

@interface TURAnselGalleryPanelController : NSObject {
    // Outlets
    IBOutlet NSTextField *galleryNameTextField;
    IBOutlet NSTextField *gallerySlugTextField;
    IBOutlet NSTextField *galleryDescTextField;
    IBOutlet NSPanel *newGallerySheet;

    // Instance members
    TURAnsel *_anselController;
    NSWindow *_controllerWindow;
    id _delegate;
}

// Actions
- (IBAction)doNewGallery: (id)sender;
- (IBAction)cancelNewGallery: (id)sender;
- (id)initWithController: (TURAnsel *)theController;
- (id)initWithController: (TURAnsel *)theController withGalleryName: (NSString *)galleryName;
- (void)showSheetForWindow: (NSWindow *)theWindow;
- (void)setDelegate: (id)theDelegate;
@end
