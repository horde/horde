//
//  AnselExportController.h
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 10/23/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
#import <Quartz/Quartz.h>
#import "ExportPluginProtocol.h"

@class TURAnsel, TURAnselGallery;
@class FBProgressController;
@class TURNewGalleryController;

// User defaults keys
extern NSString * const TURAnselServersKey;
extern NSString * const TURAnselExportSize;
extern NSString * const TURAnselDefaultServerKey;

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
    IBOutlet NSTextField *statusLabel;
    IBOutlet NSProgressIndicator *spinner;
    IBOutlet NSImageView *defaultImageView;
    IBOutlet NSButton *mNewGalleryButton;
    IBOutlet NSPopUpButton *mServersPopUp;
    IBOutlet NSButton *mCancelConnect;
    IBOutlet NSTextField *mImageCountLabel;
    
    // Gallery View
    IBOutlet NSButton *viewGallery;
    IBOutlet NSWindow *mviewGallerySheet;
    IBOutlet NSButton *closeGalleryView;
    IBOutlet IKImageBrowserView *browserView;
    NSMutableArray *browserData;
    
    
    // New Server sheet
    IBOutlet NSWindow *newServerSheet;
    IBOutlet NSTextField *mServerSheetHostURL;
    IBOutlet NSTextField *mServerSheetUsername;    
    IBOutlet NSSecureTextField *mServerSheetPassword;
    IBOutlet NSTextField *mServerSheetServerNickName;
    IBOutlet NSButton *mMakeNewServerDefault;

    
    // Server list
    IBOutlet NSPanel *serverListPanel;
    IBOutlet NSTableView *serverTable;
    
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
    
    // Remembers the selected server before it changes. Used to reselect the
    // proper server if necessary when server panels are closed.
    int mIndexOfPreviouslySelectedServer;
    
}

@property (readwrite, retain) TURAnselGallery *currentGallery;

// Getter/setter
- (NSWindow *)window;

// Actions
- (IBAction) showNewGallery: (id)sender;
- (IBAction) doAddServer: (id)sender;
- (IBAction) doCancelAddServer: (id)sender;
- (IBAction) clickServer: (id)sender;
- (IBAction) clickCancelConnect: (id)sender;

- (IBAction) clickViewGallery: (id)sender;
- (IBAction) closeGalleryView: (id)sender;

// Server List
- (IBAction) closeServerList: (id)sender;
- (IBAction) removeServer: (id)sender;

// overrides
- (void)awakeFromNib;
- (void)dealloc;


@end
