//
//  TURAnselGallery.h
//  
// Class to wrap Ansel Gallery.
//
//  Created by Michael Rubinsky on 10/21/08.
//  Copyright 2008 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
@class TURAnsel, NSURL, XMLRPCResponse;

typedef enum {
    TURAnselGalleryStateReady = 0,
    TURAnselGalleryStateBusy
} TURAnselGalleryState; 

@interface NSObject (TURAnselGalleryDelegate)
- (void)TURAnselGalleryDidReceiveRPCResponse: (XMLRPCResponse *)response;
- (void)TURAnselGalleryDidUploadImage: (id *)gallery;
@end

@interface TURAnselGallery : NSObject {
    int galleryId;
    int galleryImageCount;
    int galleryDefaultImage;
    NSURL *galleryDefaultImageURL;
    NSMutableArray *imageList;
    NSString *galleryName;
    NSString *galleryDescription;
    TURAnsel *anselController;
    TURAnselGalleryState state;
    id delegate;
}
@property (readonly) NSString *galleryName;
@property (readonly) NSString *galleryDescription;
@property (readonly) int galleryImageCount;
@property (readwrite) int galleryDefaultImage;

- (id)initWithObject:(id)galleryData controller:(TURAnsel * )controller;
- (void)uploadImageObject: (NSDictionary *)imageParameters;
- (bool)isBusy;

// Getter / Setter
- (void)setDelegate: (id)newDelegate;
- (id)delegate;
- (NSURL *)galleryDefaultImageURL;
- (id)listImages;
- (int)galleryId;
- (TURAnselGalleryState) state;
- (void)setState: (TURAnselGalleryState)theState;
- (void)setAnselController:(TURAnsel *)newController;
@end