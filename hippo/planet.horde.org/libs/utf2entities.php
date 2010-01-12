<?php

 function utf2entities($source) {
        
        // array used to figure what number to decrement from character order value
        // according to number of characters used to map unicode to ascii by utf-8
        $decrement[4] = 240;
        $decrement[3] = 224;
        $decrement[2] = 192;
        $decrement[1] = 0;
        
        // the number of bits to shift each charNum by
        $shift[1][0] = 0;
        $shift[2][0] = 6;
        $shift[2][1] = 0;
        $shift[3][0] = 12;
        $shift[3][1] = 6;
        $shift[3][2] = 0;
        $shift[4][0] = 18;
        $shift[4][1] = 12;
        $shift[4][2] = 6;
        $shift[4][3] = 0;
        
        $pos = 0;
        $len = strlen ($source);
        $encodedString = '';
        while ($pos < $len) {
            $thisLetter = substr ($source, $pos, 1);
            $asciiPos = ord ($thisLetter);
            $asciiRep = $asciiPos >> 4;
            
            if ($asciiPos < 128) {
                $pos += 1;
                $thisLen = 1;
            }
            else if ($asciiRep == 12 or $asciiRep == 13) {
                // 2 chars representing one unicode character
                $thisLetter = substr ($source, $pos, 2);
                $pos += 2;
                $thisLen = 2;
            }
            else if ($asciiRep == 15) {
                // 4 chars representing one unicode character
                $thisLetter = substr ($source, $pos, 4);
                $thisLen = 4;
                $pos += 4;
            }
            else if ($asciiRep == 14) {
                // 3 chars representing one unicode character
                $thisLetter = substr ($source, $pos, 3);
                $thisLen = 3;
                $pos += 3;
            }
            
            // process the string representing the letter to a unicode entity
            
            if ($thisLen == 1) {
                $encodedLetter =$thisLetter;
            } else {
                $thisPos = 0;
                $decimalCode = 0;
                while ($thisPos < $thisLen) {
                    $thisCharOrd = ord (substr ($thisLetter, $thisPos, 1));
                    if ($thisPos == 0) {
                        $charNum = intval ($thisCharOrd - $decrement[$thisLen]);
                        $decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
                    }
                    else {
                        $charNum = intval ($thisCharOrd - 128);
                        $decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
                    }
                    
                    $thisPos++;
                }
                if ($decimalCode < 65529) {
                    $encodedLetter = "&#". $decimalCode. ';';
                } else {
                    $encodedLetter = "";
                }
            }
            $encodedString .= $encodedLetter;
            
        }
        return $encodedString;
    }

