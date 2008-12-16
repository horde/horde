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

// User defaults keys
extern NSString * const TURAnselServersKey;
extern NSString * const TURAnselExportSize;

// Server property keys
extern NSString * const TURAnselServerNickKey;
extern NSString * const TURAnselServerEndpointKey;
extern NSString * const TURAnselServerUsernameKey;
extern NSString * const TURAnselServerPasswordKey;

@interface AnselExportController : NSObject <ExportPluginProtocol> {

    // Export manager passed in from iPhoto
    id <ExportImageProtocol> mExportMgr;
    
    // iPhoto asks for these
    IBOutlet NSBox <ExportPluginBoxProtocol> *mSettingsBox;
    IBOutlet NSControl *firstView;
    
    //Outlets   
    IBOutlet NSPopUpButton *mSizePopUp;
    IBOutlet NSComboBox *galleryCombo;
    IBOutlet NSComboBox *mServers; 
    IBOutlet NSTextField *connectedLabel;
    IBOutlet NSProgressIndicator *spinner;
    IBOutlet NSImageView *defaultImageView;
    IBOutlet NSButton *newGalleryButton;
    IBOutlet NSPopUpButton *mServersPopUp;
    
    // New Server sheet
    IBOutlet NSWindow *newServerSheet;
    IBOutlet NSTextField *anselHostURL;
    IBOutlet NSTextField *username;    
    IBOutlet NSSecureTextField *password;
    IBOutlet NSTextField *serverNickName;
    
    // Progress struct (This one is part of the protocol, but we don't use it)
    ExportPluginProgress progress;
    
    // Currently selected server data
    NSMutableArray *anselServers;
    NSDictionary *currentServer;
    
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
- (IBAction) showNewGallery: (id)sender;
- (IBAction) doAddServer: (id)sender;
- (IBAction) doCancelAddServer: (id)sender;
- (IBAction) clickServer: (id)sender;

// overrides
- (void)awakeFromNib;
- (void)dealloc;

@end
