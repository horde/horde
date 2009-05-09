//
//  TURAnselGalleryNode.h
//  iPhoto2Ansel
//
//  Created by Michael Rubinsky on 5/9/09.
//  Copyright 2009 __MyCompanyName__. All rights reserved.
//

#import <Cocoa/Cocoa.h>
@class TURAnselGallery;

@interface TURAnselGalleryNode : NSObject {
    TURAnselGallery *data;           // Pointer to the data for this gallery
    NSArray *children;               // This gallery's children
}

- (NSArray *)children;
- (TURAnselGallery *)data;

-(void)addChild: (TURAnselGallery *)anselGallery;
-(void)setData: (TURAnselGallery *)anselGallery;
@end
