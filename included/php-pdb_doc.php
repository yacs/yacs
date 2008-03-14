<?PHP
/** Class extender for PalmOS DOC files
 *
 * Copyright (C) 2001 - PHP-PDB development team
 * Licensed under the GNU LGPL
 * See the doc/LEGAL file for more information
 * See http://php-pdb.sourceforge.net/ for more information about the library
 *
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 * @link http://php-pdb.sourceforge.net/
 */

define('PDB_DOC_RECORD_SIZE', 4096);

class PalmDoc extends PalmDB {
   var $Bookmarks = array();  // Bookmarks stored in the doc file
                              // $Bookmarks[position] = "name"
   var $IsCompressed = false;
   var $CompressedData = array();  // Filled when saving DOC file


   function PalmDoc ($Title = '', $Compressed = true) {
      PalmDB::PalmDB('TEXt', 'REAd', $Title);

      $this->EraseDocText();
      $this->IsCompressed = $Compressed;
   }


   // Gets all of the document's text and returns it as a string
   function GetDocText () {
      $String = '';
      $i = 1;
      while (isset($this->Records[$i])) {
         $String .= pack('H*', $this->Records[$i]);
	 $i ++;
      }
      return $String;
   }


   // Erases all text in the document
   function EraseDocText () {
      $this->Records = array();
      // Record 0 is reserved for header information
      $this->GoToRecord(1);
   }


   // Appends $String to the end of the document
   function AddDocText ($String) {
      // Temporarily say the DOC is not compressed so that we get the
      // real size of the record
      $isCompressed = $this->IsCompressed;
      $this->IsCompressed = false;

      $SpaceLeft = PDB_DOC_RECORD_SIZE - $this->GetRecordSize();
      while ($String) {
         if ($SpaceLeft > 0) {
	    $this->AppendString($String, $SpaceLeft);
	    $String = substr($String, $SpaceLeft);
	    $SpaceLeft = PDB_DOC_RECORD_SIZE - $this->GetRecordSize();
	 } else {
	    $this->GoToRecord('+1');
	    $SpaceLeft = PDB_DOC_RECORD_SIZE;
	 }
      }

      // Return to the correct IsCompressed true/false state
      $this->IsCompressed = $isCompressed;
   }


   // Creates the informational record (record 0)
   // Used only for writing the file
   function MakeDocRecordZero () {
      $oldRec = $this->GoToRecord(0);
      $this->DeleteRecord();
      if ($this->IsCompressed)
         $this->AppendInt16(2);  // "Version"   2 = compressed
      else
         $this->AppendInt16(1);  // "Version"   1 = uncompressed
      $this->AppendInt16(0);  // Reserved

      $Content_Length = 0;
      $MaxIndex = 0;
      ksort($this->Records, SORT_NUMERIC);
      $keys = array_keys($this->Records);
      array_shift($keys);
      $MaxIndex = array_pop($keys);
      $keys[] = $MaxIndex;

      // Temporarily say the doc is uncompressed so that we get
      // the real length of the uncompressed record
      $isCompressed = $this->IsCompressed;
      $this->IsCompressed = false;

      foreach ($keys as $index) {
         $Content_Length += $this->GetRecordSize($index);
	 $this->RecordAttrs[$index] = 0x40;  // dirty + private
      }

      // Return to the correct state of IsCompressed
      $this->IsCompressed = $isCompressed;

      $this->AppendInt32($Content_Length);       // Doc Size
      $this->AppendInt16($MaxIndex);             // Number of Records
      $this->AppendInt16(PDB_DOC_RECORD_SIZE);   // Record size
      $this->AppendInt32(0);                     // Reserved
         // possibly used for position in doc?
	 // Don't care -- we are merely creating the doc file

      $this->GoToRecord($oldRec);
   }


   // Overrides the output function
   function WriteToStdout() {
      if ($this->IsCompressed)
         $this->CompressData();
      PalmDB::WriteToStdout();
   }


   // Overrides the save function
   function WriteToFile($file) {
      if ($this->IsCompressed)
         $this->CompressData();
      PalmDB::WriteToFile($file);
   }


   // Returns the size of the record specified, or the current record if
   // no record is specified
   function GetRecordSize($num = false) {
      if ($num === false)
         $num = $this->CurrentRecord;
      if ($num == 0)
         return 16;
      if (! isset($this->Records[$num])) {
         $bookmark = -1;
	 while (! isset($this->Records[$num]) && $num > 0) {
	    $bookmark ++;
	    $num --;
	 }
	 if ($bookmark < count($this->Bookmarks))
	    return 20;
      }
      // If it is compressed, GetRecord() will compress the record before
      // returning the data.  Since the data is hex encoded, divide the
      // size of the resulting string by 2.
      if ($this->IsCompressed)
         return strlen($this->CompressedData[$num]) / 2;
      return PalmDB::GetRecordSize($num);
   }


   // Returns the data of the specified record, or the current record if no
   // record is specified.  If the record doesn't exist, returns ''.
   function GetRecord($num = false) {
      if ($num === false)
         $num = $this->CurrentRecord;

      if ($num == 0) {
         $this->MakeDocRecordZero();
	 return $this->Records[0];
      }

      if (! isset($this->Records[$num])) {
         $bookmark = -1;
	 while (! isset($this->Records[$num]) && $num > 0) {
	    $bookmark ++;
	    $num --;
	 }
	 // Sort bookmarks in order of appearance
	 ksort($this->Bookmarks);
	 if ($bookmark < count($this->Bookmarks)) {
  	    $Positions = array_keys($this->Bookmarks);
	    $Desired = $this->Bookmarks[$Positions[$bookmark]];
	    $str = $this->String($Desired, 15);
	    $str = $this->PadString($str, 16);
	    $str .= $this->Int32($Positions[$bookmark]);
	    return $str;
	 }
         return '';
      }

      if ($this->IsCompressed)
         return $this->CompressedData[$num];

      return $this->Records[$num];
   }


   // Compresses the entire doc file
   // The compressed information is cached for better performance with
   // successive writes
   function CompressData() {
      $this->CompressedData = array();
      foreach ($this->Records as $index => $str) {
	 $this->CompressedData[$index] = $this->CompressRecord($str);
      }
   }


   // Compresses a single string.  Please note that the string passed in and
   // the string returned are both hex encoded!
   //
   // 0x00 = represents itself
   // 0x01 - 0x08 = Read next n bytes verbatim
   // 0x09 - 0x7F = Represents itself
   // 0x80 - 0xBF = Read next byte to make 16-bit number.  Remove top 2 bits.
   //               Next 11 bits = how far back to read.  Last 3 bits should
   //               be (# of bytes to copy - 3), with the last three bits
   //               never being zero.
   // 0xC0 - 0xFF = Space + 7-bit char
   //
   // If I use *1 or *2 for compress code bytes, I can illustrate a few
   // problems with this compression code.  I think that every byte counts
   // on such a limited device, so maybe this compression code could be
   // optimized a bit more.  Anyone have a good plan?
   //
   //  abcdefghijgabcgabcdefghij
   // should compress to
   //  abcdefghijgabcg*1     *1 = abcdefghij
   // instead of
   //  abcdefghijgabc*1*2    *1 = gabc   *2 = defghij
   //
   // Admittedly, the loss is small, and possibly would take lots of CPU
   // time to remove a single byte, but maybe there is an efficient
   // algorithm out there that I'm not finding.
   //
   // I've tried thinking of a recusion technique and a looping technique, but
   // I can't think of anything that will provide the best compression in
   // every circumstance.  Until then, I'll just keep this semi-fast stuff
   // here.
   function CompressRecord($In) {
      $Out = '';
      $Literal = '';
      $pos = 0;

      $pos = 0;
      while ($pos < strlen($In)) {
         // Search for a string
	 $lastMatchPos = 0;
	 $lastMatchSize = 2;
	 $Key = substr($In, $pos, 2);

         // Start one character before what we want.
         $StartingPos = $pos - 4094;
	 if ($StartingPos < 0)
	    $StartingPos = -2;

	 // Moves a minimum of 1 character
	 $StartingPos = $this->FindNextStartingPos($In, $pos, $lastMatchSize,
	    $StartingPos);

	 while ($StartingPos != $pos) {
	    // Attempt the matching
	    // Remember -- $pos and $potential are pointing at hex-encoded
	    // strings!
	    $size = ($lastMatchSize + 1) * 2;

	    if ($size > 20)
	       $size = 21;
	    while ($size < 21 &&
	           $size + $StartingPos < $pos &&
		   $pos + $size < strlen($In) &&
		   $In[$StartingPos + $size] == $In[$pos + $size])
	       $size ++;
	    if ($size % 2)
	       $size --;
	    if ($size / 2 > $lastMatchSize) {
	       $lastMatchPos = ($pos - $StartingPos) / 2;
	       $lastMatchSize = $size / 2;
	    }

	    // Move $StartingPos ahead
	    $StartingPos = $this->FindNextStartingPos($In, $pos,
	       $lastMatchSize, $StartingPos);
	 }

	 // Done searching.  If we found a match that works ...
	 if ($lastMatchSize > 2) {
	    // Use a simple form of LZ77
	    $pos += $lastMatchSize * 2;
	    $lastMatchSize -= 3;
	    $lastMatchSize = $lastMatchSize & 0x07;
	    $lastMatchPos = $lastMatchPos << 3;
	    $lastMatchPos = $lastMatchPos & 0x3FF8;
	    $Command = 0x8000 + $lastMatchPos + $lastMatchSize;
	    if ($Literal != '') {
	       $Out .= $this->EncodeLiteral($Literal);
	       $Literal = '';
	    }
	    $Out .= $this->Int16($Command);
	 } else {
  	    $KeyVal = hexdec($Key);
	    if ($Literal != '' && substr($Literal, -2) == '20' &&
	        $KeyVal >= 0x40 && $KeyVal <= 0x7F) {
	       // Space encoding of a normal character
	       $Literal = substr($Literal, 0, strlen($Literal) - 2);
	       if ($Literal != '') {
	          $Out .= $this->EncodeLiteral($Literal);
		  $Literal = '';
	       }
	       $KeyVal += 0x80;
	       $Out .= sprintf('%02x', $KeyVal & 0xFF);
	       $pos += 2;
	    } else {
	       // Literal encoding of char
	       $Literal .= $Key;
	       $pos += 2;
	    }
	 }
      }

      if ($Literal != '')
         $Out .= $this->EncodeLiteral($Literal);

      return $Out;
   }


   // Finds the next possible spot for compression
   function FindNextStartingPos($In, $pos, $matchSize, $startingPos) {
      // If we found a match that consumed the rest of the string, we found
      // the best match already
      if (strlen($In) - $pos <= $matchSize * 2)
         return $pos;

      // Step ahead 1 char
      $startingPos += 2;

      while (1) {
         // Look for a match that has one more character
         $startingPos = strpos($In, substr($In, $pos,
	    ($matchSize + 1) * 2), $startingPos);

	 // If no more matches, return $pos
	 if ($startingPos === false)
	    return $pos;

	 // Make sure that we don't go too far
	 if ($startingPos + ($matchSize * 2) >= $pos)
	    return $pos;

	 // If we are on an even char (remember?  we are
	 // hex encoded)
	 if ($startingPos % 2 == 0)
	    return $startingPos;

	 // We are not on an even char -- skip ahead 1/2 of a char
	 // and then search again
	 $startingPos ++;
      }
   }


   // Encodes the literal string for the CompressRecord() function
   function EncodeLiteral($Literal) {
      $pos = 0;
      $Out = '';
      while ($pos < strlen($Literal)) {
         $Key = substr($Literal, $pos, 2);
	 $KeyValue = hexdec($Key);
	 if ($KeyValue == 0 || ($KeyValue >= 0x09 && $KeyValue <= 0x7f)) {
	    $Out .= $Key;
	    $pos += 2;
	 } else {
	    $L = strlen($Literal) - $pos;
	    if ($L > 16)
	       $L = 16;
	    $Out .= '0' . ($L / 2) . substr($Literal, $pos, $L);
	    $pos += $L;
	 }
      }

      return $Out;
   }


   // Returns a list of records to write to a file in the order specified.
   function GetRecordIDs() {
      $ids = PalmDB::GetRecordIDs();
      if (! isset($this->Records[0]))
         array_unshift($ids, 0);
      $Max = 0;
      foreach ($ids as $val) {
         if ($Max <= $val)
	    $Max = $val + 1;
      }
      foreach ($this->Bookmarks as $val) {
         $ids[] = $Max ++;
      }
      return $ids;
   }


   // Returns the number of records to write
   function GetRecordCount() {
      $c = count($this->Records);
      if (! isset($this->Records[0]) && $c)
         $c ++;
      $c += count($this->Bookmarks);
      return $c;
   }


   // Adds a bookmark.
   // $Name must be 15 chars or less (automatically trimmed)
   // $Pos is the position to add the bookmark at, or the current position if
   // not specified
   // Returns true on error
   // If $Pos already has a bookmark defined, this will blindly overwrite that
   // bookmark.
   function AddBookmark($Name, $Pos = false) {
      if ($Name == '')
         return true;
      if ($Pos === false) {
         $Pos = 0;
	 // Temporarily set the IsCompressed to false so that we get an
	 // accurate reading of the # of uncompressed bytes
	 $isCompressed = $this->IsCompressed;
	 $this->IsCompressed = false;

         foreach ($this->Records as $id => $data) {
	    if ($id != 0) {
	       $Pos += $this->GetRecordSize($id);
	    }
	 }

	 // Set the IsCompressed back to what it was originally
	 $this->IsCompressed = $isCompressed;
      }
      $this->Bookmarks[$Pos] = $Name;
      return false;
   }


   function ReadFile($file) {
      $Ret = PalmDB::ReadFile($file);
      if ($Ret != false)
         return $Ret;
      if (! isset($this->Records[0]))
         return true;
      if ($this->ParseRecordZero())
         return true;
      if ($this->IsCompressed)
         $this->DecompressRecords();
   }


   function ParseRecordZero() {
      // Int16 = Version  [0-3]
      // Int16 = reserved  [4-7]
      // Int32 = uncompressed doc size  [8-15]
      // Int16 = Number of records  [16-19]
      // Int16 = Record size
      // Int32 = reserved (current spot in doc?)

      // Reads info from the header
      // Also rips out bookmarks
      $Version = substr($this->Records[0], 0, 4);
      $Version = hexdec($Version);
      if ($Version == 1)
         $this->IsCompressed = false;
      elseif ($Version == 2)
         $this->IsCompressed = true;
      else
         return true;

      // Rip out bookmarks
      $RecordNumber = substr($this->Records[0], 16, 4);
      $RecordNumber = hexdec($RecordNumber);
      foreach ($this->Records as $index => $data) {
         if ($index > $RecordNumber) {
	    // 16 bytes = bookmark name
	    // Int32 = Spot
	    $name = substr($data, 0, 32);
	    $name = pack('H*', $name);
	    $spot = substr($data, 32, 8);
	    $spot = hexdec($spot);
	    $this->Bookmarks[$spot] = $name;
	    unset($this->Records[$index]);
	 }
      }

      unset($this->Records[0]);

      return false;
   }

   function DecompressRecords() {
      foreach ($this->Records as $index => $data) {
         $this->Records[$index] = $this->DecompressRecord($data);
      }
   }


   function DecompressRecord($data) {
      $pos = 0;
      $Out = '';
      while ($pos < strlen($data)) {
         $Key = substr($data, $pos, 2);
	 $KeyVal = hexdec($Key);

	 if ($KeyVal == 00 || ($KeyVal >= 0x09 && $KeyVal <= 0x7F)) {
	    // Represents itself
	    $pos += 2;
	    $Out .= $Key;
	 } elseif ($KeyVal >= 0x01 && $KeyVal <= 0x08) {
	    // Read next N bytes verbatim
	    $Out .= substr($data, $pos, $KeyVal * 2);
	    $pos += $KeyVal * 2;
	 } elseif ($KeyVal >= 0xC0 && $KeyVal <= 0xFF) {
	    // Space + 7-bit char
	    $Out .= '20' . sprintf('%02x', $KeyVal & 0x7F);
	    $pos += 2;
	 } else {
	    // Like LZ77 compression
	    $BigByte = $KeyVal & 0x3F;
	    $BigByte = $BigByte << 8;
	    $BigByte += hexdec(substr($data, $pos + 2, 2));
	    $pos += 4;

	    $CopyBits = $BigByte & 0x7;
	    $CopyBits += 3;

	    $PosBits = $BigByte >> 3;
	    $PosBits &= 0x7FF;
	    $PosBits = strlen($Out) - ($PosBits * 2);
	    if ($PosBits >= 0)
	       $Out .= substr($Out, $PosBits, $CopyBits * 2);
	 }
      }

      return $Out;
   }
}

?>