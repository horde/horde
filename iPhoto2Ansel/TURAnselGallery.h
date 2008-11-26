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
- (void)TURAnselGalleryDidUploadImage: (TURAnselGallery *)gallery;
- (void)TURAnselGalleryDidReceiveDefaultURL: (TURAnselGallery *)gallery;
@end

@interface TURAnselGallery : NSObject {
    int galleryId;
    int galleryImageCount;
    int galleryDefaultImage;
    NSURL *galleryDefaultImageURL;
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
@property (readonly, retain) NSURL *galleryDefaultImageURL;

- (id)initWithObject:(id)galleryData controller:(TURAnsel *)controller;
- (int)galleryId;
- (void)requestDefaultImageURL;
- (void)parseImageUrlRequest:(XMLRPCResponse *)response;
- (void)uploadImageObject: (NSDictionary *)imageParameters;
- (void)setDelegate: (id)newDelegate;
- (id)delegate;
- (TURAnselGalleryState) state;
- (void)setState: (TURAnselGalleryState)theState;
- (bool)isBusy;
- (void)setAnselController:(TURAnsel *)newController;
@end