//
//  AnselExportController.h
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 10/23/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import "ExportPluginProtocol.h"

@class TURAnsel, TURAnselGallery;
@class FBProgressController;
@class TURNewGalleryController;

@interface AnselExportController : NSObject <ExportPluginProtocol> {

    // Export manager passed in from iPhoto
    id <ExportImageProtocol> mExportMgr;
    
    // iPhoto asks for these
    IBOutlet NSBox <ExportPluginBoxProtocol> *mSettingsBox;
    IBOutlet NSControl *firstView;
    
    //Outlets   
    IBOutlet NSPopUpButton *mSizePopUp;
    IBOutlet NSPopUpButton *mQualityPopUp;
    IBOutlet NSTextField *anselHostURL;
    IBOutlet NSTextField *username;
    IBOutlet NSSecureTextField *password;
    IBOutlet NSComboBox *galleryCombo;
    IBOutlet NSTextField *connectedLabel;
    IBOutlet NSButton *beginButton;
    IBOutlet NSButton *newGalleryButton;
    IBOutlet NSProgressIndicator *spinner;
    IBOutlet NSWindow *newGallerySheet;
    IBOutlet NSImageView *defaultImageView;
        
    // Progress struct (This one is part of the protocol, but we don't use it)
    ExportPluginProgress progress;
    
    // This is our real progress controller (stolen from Facebook exporter).
    FBProgressController *progressController;
    
    // Mutex lock (required for the protocol, but not used)
    NSRecursiveLock *progressLock;

    BOOL cancelExport;

    TURAnsel *anselController;
    TURAnselGallery *currentGallery;
    int currentImageCount;
}

@property (readwrite, retain) TURAnselGallery *currentGallery;

// Getter/setter
- (NSWindow *)window;

// Actions
- (IBAction) doConnect: (id)sender;
- (IBAction) showNewGallery: (id)sender;

// overrides
- (void)awakeFromNib;
- (void)dealloc;

@end
