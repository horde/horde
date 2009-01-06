//
// Copyright Zach Wily
// All rights reserved.
// 
// Redistribution and use in source and binary forms, with or without modification, 
// are permitted provided that the following conditions are met:
// 
// - Redistributions of source code must retain the above copyright notice, this 
//     list of conditions and the following disclaimer.
// 
// - Redistributions in binary form must reproduce the above copyright notice, this
//     list of conditions and the following disclaimer in the documentation and/or 
//     other materials provided with the distribution.
// 
// - Neither the name of Zach Wily nor the names of its contributors may be used to 
//     endorse or promote products derived from this software without specific prior 
//     written permission.
// 
//  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND 
//  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED 
//  WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE 
//  DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR 
//  ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES 
//  (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
//   LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON 
//  ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT 
//  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS 
//  SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
//

#import "ImageResizer.h"
#import "QuickTime/QuickTime.h"

Handle myCreateHandleDataRef(
                             Handle             dataHandle,
                             Str255             fileName,
                             OSType             fileType,
                             StringPtr          mimeTypeString,
                             Ptr                initDataPtr,
                             Size               initDataByteCount
                             );

NSSize getGoodSize(NSSize size, NSSize maxSize);

@implementation ImageResizer

+ (NSData*) getScaledImageFromData:(NSData*)data toSize:(NSSize)size {
    NSData *scaledImageData;
    
    Handle imageDataH = NULL;
    PtrToHand([data bytes], &imageDataH, [data length]);
    Handle dataRef = myCreateHandleDataRef(imageDataH, "\pdummy.jpg", kQTFileTypeJPEG, nil, nil, 0);
    
    // create a Graphics Importer component that will read from the PNG data
    ComponentInstance importComponent=0, exportComponent=0;
    GetGraphicsImporterForDataRef(dataRef, HandleDataHandlerSubType, &importComponent);  // TODO: check return value
    DisposeHandle(dataRef);
    
    // get metadata
    UserData imageMetadata;
    NewUserData(&imageMetadata);
    GraphicsImportGetMetaData(importComponent, imageMetadata);
    //GraphicsImportGetDescription(importComponent, &imageDescription);
    
    Rect naturalBounds, scaledBounds;
    GraphicsImportGetNaturalBounds(importComponent, &naturalBounds);
    
    NSSize scaledSize = getGoodSize(NSMakeSize(naturalBounds.right, naturalBounds.bottom), size);
    
    scaledBounds = naturalBounds;
    scaledBounds.right = scaledSize.width;
    scaledBounds.bottom = scaledSize.height;
    
    GraphicsImportSetBoundsRect( importComponent, &scaledBounds );

    // Now the exporter
    OpenADefaultComponent(GraphicsExporterComponentType, kQTFileTypeJPEG, &exportComponent);
    
    GraphicsExportSetInputGraphicsImporter(exportComponent, importComponent);
    
    Handle scaledImageDataH = NewHandle(0);
    GraphicsExportSetOutputHandle(exportComponent, scaledImageDataH);

    GraphicsExportSetMetaData(exportComponent, imageMetadata);
//    GraphicsExportSetDescription(exportComponent, imageDescription);
    GraphicsExportSetExifEnabled(exportComponent, TRUE);

    unsigned long actualSizeWritten = 0;
    GraphicsExportDoExport(exportComponent, &actualSizeWritten);
    HLock(scaledImageDataH);
    scaledImageData = [NSData dataWithBytes:*scaledImageDataH 
                                     length:GetHandleSize(scaledImageDataH)];
    HUnlock(scaledImageDataH);

    DisposeHandle(scaledImageDataH);
    CloseComponent(exportComponent);
    DisposeUserData(imageMetadata);
    CloseComponent(importComponent);
    DisposeHandle(imageDataH);

    return scaledImageData;
}

NSSize getGoodSize(NSSize size, NSSize maxSize) {    
    int old_x = size.width;
    int old_y = size.height;
    int new_x = maxSize.width;
    int new_y = maxSize.height;
    
    // flip the Max dimensions if our source is taller than wide
    if (old_y > old_x) {
        new_x = maxSize.height;
        new_y = maxSize.width;
    }
    
    int good_x;
    int good_y;
    
    float aspect = (float)old_x / (float)old_y;
    
    if (aspect >= 1) {
        good_x = new_x;
        good_y = new_x / aspect;
        
        if (good_y > new_y) {
            good_y = new_y;
            good_x = new_y * aspect;
        }
    }
    else {
        good_y = new_y;
        good_x = aspect * new_y;
        
        if (good_x > new_x) {
            good_x = new_x;
            good_y = new_x / aspect;
        }
    }
    // Don't go any bigger!
    if ((good_x > old_x) || (good_y > old_y)) {
        good_x = old_x;
        good_y = old_y;
    }

    return NSMakeSize(good_x, good_y);
}

Handle myCreateHandleDataRef(
                             Handle             dataHandle,
                             Str255             fileName,
                             OSType             fileType,
                             StringPtr          mimeTypeString,
                             Ptr                initDataPtr,
                             Size               initDataByteCount
                             )
{
    OSErr        err;
    Handle    dataRef = nil;
    Str31        tempName;
    long        atoms[3];
    StringPtr    name;
    
    
    // First create a data reference handle for our data
    err = PtrToHand( &dataHandle, &dataRef, sizeof(Handle));
    if (err) goto bail;
    
    // If this is QuickTime 3 or later, we can add
    // the filename to the data ref to help importer
    // finding process. Find uses the extension.
    
    name = fileName;
    if (name == nil)
    {
        tempName[0] = 0;
        name = tempName;
    }
    
    // Only add the file name if we are also adding a
    // file type, MIME type or initialization data
    if ((fileType) || (mimeTypeString) || (initDataPtr))
    {
        err = PtrAndHand(name, dataRef, name[0]+1);
        if (err) goto bail;
    }
    
    // If this is QuickTime 4, the handle data handler
    // can also be told the filetype and/or
    // MIME type by adding data ref extensions. These
    // help the importer finding process.
    // NOTE: If you add either of these, you MUST add
    // a filename first -- even if it is an empty Pascal
    // string. Under QuickTime 3, any data ref extensions
    // will be ignored.
    
    // to add file type, you add a classic atom followed
    // by the MacOS filetype for the kind of file
    
    if (fileType)
    {
        atoms[0] = EndianU32_NtoB(sizeof(long) * 3);
        atoms[1] = EndianU32_NtoB(kDataRefExtensionMacOSFileType);
        atoms[2] = EndianU32_NtoB(fileType);
        
        err = PtrAndHand(atoms, dataRef, sizeof(long) * 3);
        if (err) goto bail;
    }
    
    
    // to add MIME type information, add a classic atom followed by
    // a Pascal string holding the MIME type
    
    if (mimeTypeString)
    {
        atoms[0] = EndianU32_NtoB(sizeof(long) * 2 + mimeTypeString[0]+1);
        atoms[1] = EndianU32_NtoB(kDataRefExtensionMIMEType);
        
        err = PtrAndHand(atoms, dataRef, sizeof(long) * 2);
        if (err) goto bail;
        
        err = PtrAndHand(mimeTypeString, dataRef, mimeTypeString[0]+1);
        if (err) goto bail;
    }
    
    // add any initialization data, but only if a dataHandle was
    // not already specified (any initialization data is ignored
    // in this case)
    if((dataHandle == nil) && (initDataPtr))
    {
        
        atoms[0] = EndianU32_NtoB(sizeof(long) * 2 + initDataByteCount);
        atoms[1] = EndianU32_NtoB(kDataRefExtensionInitializationData);
        
        err = PtrAndHand(atoms, dataRef, sizeof(long) * 2);
        if (err) goto bail;
        
        err = PtrAndHand(initDataPtr, dataRef, initDataByteCount);
        if (err) goto bail;
    }
    
    return dataRef;
    
bail:
        if (dataRef)
        {
            // make sure and dispose the data reference handle
            // once we are done with it
            DisposeHandle(dataRef);
        }
    
    return nil;
}


@end
