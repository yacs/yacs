<?PHP
/** PHP-PDB -- PHP class to write PalmOS databases.
 *
 * Copyright (C) 2001 - PHP-PDB development team
 * Licensed under the GNU LGPL software license.
 * See the doc/LEGAL file for more information
 * See http://php-pdb.sourceforge.net/ for more information about the library
 *
 *
 * As a note, storing all of the information as hexadecimal kinda sucks,
 * but it is tough to store and properly manipulate a binary string in
 * PHP.  We double the size of the data but decrease the difficulty level
 * immensely.
 *
 * @reference
 * @license http://www.gnu.org/copyleft/lesser.txt GNU Lesser General Public License
 * @link http://php-pdb.sourceforge.net/
 */

/*
 * Define constants
 */

// Sizes
define('PDB_HEADER_SIZE', 72); // Size of the database header
define('PDB_INDEX_HEADER_SIZE', 6); // Size of the record index header
define('PDB_RECORD_HEADER_SIZE', 8); // Size of the record index entry
define('PDB_RESOURCE_SIZE', 10);  // Size of the resource index entry
define('PDB_EPOCH_1904', 2082844800); // Difference between Palm's time and Unix

// Attribute Flags
define('PDB_ATTRIB_RESOURCE', 0x01);
define('PDB_ATTRIB_READ_ONLY', 0x02);
define('PDB_ATTRIB_APPINFO_DIRTY', 0x04);
define('PDB_ATTRIB_BACKUP', 0x08);
define('PDB_ATTRIB_OK_NEWER', 0x10);
define('PDB_ATTRIB_RESET', 0x20);
define('PDB_ATTRIB_OPEN', 0x40);
// Where are 0x80 and 0x100?
define('PDB_ATTRIB_LAUNCHABLE', 0x200);

// Record Flags
// The first nibble is reserved for the category number
// See PDB_CATEGORY_MASK
define('PDB_RECORD_ATTRIB_ARCHIVE', 0x08);  // Special -- see below
define('PDB_RECORD_ATTRIB_PRIVATE', 0x10);
define('PDB_RECORD_ATTRIB_DELETED', 0x20);
define('PDB_RECORD_ATTRIB_DIRTY', 0x40);
define('PDB_RECORD_ATTRIB_EXPUNGED', 0x80);
define('PDB_RECORD_ATTRIB_DEL_EXP', 0xA0);  // Mask for easier use
define('PDB_RECORD_ATTRIB_MASK', 0xF0);  // The 4 bytes for the attributes
define('PDB_RECORD_ATTRIB_CATEGORY_MASK', 0xFF);  // 1 byte

/* The archive bit should only be used when the record is deleted or
 * expunged.
 *
 * if ($attr & PDB_RECORD_ATTRIB_DEL_EXP) {
 *    // Lower 3 bits (0x07) should be 0
 *    if ($attr & PDB_RECORD_ATTRIB_ARCHIVE)
 *       echo "Record is deleted/expunged and should be archived.\n";
 *    else
 *       echo "Record is deleted/expunged and should not be archived.\n";
 * } else {
 *    // Lower 4 bits are the category
 *    echo "Record is not deleted/expunged.\n";
 *    echo "Record's category # is " . ($attr & PDB_CATEGORY_MASK) . "\n";
 * }
 */

// Category support
define('PDB_CATEGORY_NUM', 16);  // Number of categories
define('PDB_CATEGORY_NAME_LENGTH', 16);  // Bytes allocated for name
define('PDB_CATEGORY_SIZE', 276); // 2 + (num * length) + num + 1 + 1
define('PDB_CATEGORY_MASK', 0x0f);  // Bitmask -- use with attribute of record
                                    // to get the category ID


// Double conversion
define('PDB_DOUBLEMETHOD_UNTESTED', 0);
define('PDB_DOUBLEMETHOD_NORMAL', 1);
define('PDB_DOUBLEMETHOD_REVERSE', 2);
define('PDB_DOUBLEMETHOD_BROKEN', 3);

/*
 * PalmDB Class
 *
 * Contains all of the required methods and variables to write a pdb file.
 * Extend this class to provide functionality for memos, addresses, etc.
 */
class PalmDB {
   var $Records = array();     // All of the data from the records is here
                               // Key = record ID
   var $RecordAttrs = array(); // And their attributes are here
   var $CurrentRecord = 1;     // Which record we are currently editing
   var $Name = '';             // Name of the PDB file
   var $TypeID = '';           // The 'Type' of the file (4 chars)
   var $CreatorID = '';        // The 'Creator' of the file (4 chars)
   var $Attributes = 0;        // Attributes (bitmask)
   var $Version = 0;           // Version of the file
   var $ModNumber = 0;         // Modification number
   var $CreationTime = 0;      // Stored in unix time (Jan 1, 1970)
   var $ModificationTime = 0;  // Stored in unix time (Jan 1, 1970)
   var $BackupTime = 0;        // Stored in unix time (Jan 1, 1970)
   var $AppInfo = '';          // Basic AppInfo block
   var $SortInfo = '';         // Basic SortInfo block
   var $DoubleMethod = PDB_DOUBLEMETHOD_UNTESTED;
                               // What method to use for converting doubles
   var $CategoryList = array();  // Category data (not used by default --
                               // See "Category Support" comment below)


   // Creates a new database class
   function PalmDB($Type = '', $Creator = '', $Name = '') {
      $this->TypeID = $Type;
      $this->CreatorID = $Creator;
      $this->Name = $Name;
      $this->CreationTime = time();
      $this->ModificationTime = time();
   }


   /*
    * Data manipulation functions
    *
    * These convert various numbers and strings into the hexadecimal
    * format that is used internally to construct the file.  We use hex
    * encoded strings since that is a lot easier to work with than binary
    * data in strings, and we can easily tell how big the true value is.
    * B64 encoding does some odd stuff, so we just make the memory
    * consumption grow tremendously and the complexity level drops
    * considerably.
    */

   // Converts a byte and returns the value
   function Int8($value) {
      $value &= 0xFF;
      return sprintf("%02x", $value);
   }


   // Loads a single byte as a number from the file
   // Use if you want to make your own ReadFile function
   function LoadInt8($file) {
      if (is_resource($file))
         $string = fread($file, 1);
      else
         $string = $file;
      return ord($string[0]);
   }


   // Converts an integer (two bytes) and returns the value
   function Int16($value) {
      $value &= 0xFFFF;
      return sprintf("%02x%02x", $value / 256, $value % 256);
   }


   // Loads two bytes as a number from the file
   // Use if you want to make your own ReadFile function
   function LoadInt16($file) {
      if (is_resource($file))
         $string = fread($file, 2);
      else
         $string = $file;
      return ord($string[0]) * 256 + ord($string[1]);
   }


   // Converts an integer (three bytes) and returns the value
   function Int24($value) {
      $value &= 0xFFFFFF;
      return sprintf("%02x%02x%02x", $value / 65536,
                     ($value / 256) % 256, $value % 256);
   }


   // Loads three bytes as a number from the file
   // Use if you want to make your own ReadFile function
   function LoadInt24($file) {
      if (is_resource($file))
         $string = fread($file, 3);
      else
	 $string = $file;
      return ord($string[0]) * 65536 + ord($string[1]) * 256 +
         ord($string[2]);
   }


   // Converts an integer (four bytes) and returns the value
   // 32-bit integers have problems with PHP when they are bigger than
   // 0x80000000 (about 2 billion) and that's why I don't use pack() here
   function Int32($value) {
      $negative = false;
      if ($value < 0) {
         $negative = true;
	 $value = - $value;
      }
      $big = $value / 65536;
      settype($big, 'integer');
      $little = $value - ($big * 65536);
      if ($negative) {
         // Little must contain a value
         $little = - $little;
	 // Big might be zero, and should be 0xFFFF if that is the case.
	 $big = 0xFFFF - $big;
      }
      $value = PalmDB::Int16($big) . PalmDB::Int16($little);
      return $value;
   }


   // Loads a four-byte string from a file into a number
   // Use if you want to make your own ReadFile function
   function LoadInt32($file) {
      if (is_resource($file))
         $string = fread($file, 4);
      else
         $string = $file;
      $value = 0;
      $i = 0;
      while ($i < 4) {
         $value *= 256;
	 $value += ord($string[$i]);
	 $i ++;
      }
      return $value;
   }


   // Returns the method used for generating doubles
   function GetDoubleMethod() {
      if ($this->DoubleMethod != PDB_DOUBLEMETHOD_UNTESTED)
         return $this->DoubleMethod;

      $val = bin2hex(pack('d', 10.53));
      $val = strtolower($val);
      if (substr($val, 0, 4) == '8fc2')
	 $this->DoubleMethod = PDB_DOUBLEMETHOD_REVERSE;
      if (substr($val, 0, 4) == '4025')
	 $this->DoubleMethod = PDB_DOUBLEMETHOD_NORMAL;
      if ($this->DoubleMethod == PDB_DOUBLEMETHOD_UNTESTED)
	 $this->DoubleMethod = PDB_DOUBLEMETHOD_BROKEN;

      return $this->DoubleMethod;
   }



   // Converts the number into a double and returns the encoded value
   // Not sure if this will work on all platforms.
   // Double(10.53) should return "40250f5c28f5c28f"
   function Double($value) {
      $method = $this->GetDoubleMethod();

      if ($method == PDB_DOUBLEMETHOD_BROKEN)
         return '0000000000000000';

      $value = bin2hex(pack('d', $value));

      if ($method == PDB_DOUBLEMETHOD_REVERSE)
         $value = substr($value, 14, 2) . substr($value, 12, 2) .
            substr($value, 10, 2) . substr($value, 8, 2) .
   	    substr($value, 6, 2) . substr($value, 4, 2) .
	    substr($value, 2, 2) . substr($value, 0, 2);

      return $value;
   }


   // The reverse?  May not work on your platform.
   // Use if you want to make your own ReadFile function
   function LoadDouble($file) {
      $method = $this->GetDoubleMethod();

      if ($method == PDB_DOUBLEMETHOD_BROKEN)
         return 0.0;

      if (is_resource($file))
         $string = fread($file, 8);
      else
	 $string = $file;

      // Reverse the bytes... this might not be nessesary
      // if PHP is running on a big-endian server
      if ($method == PDB_DOUBLEMETHOD_REVERSE)
         $string = substr($string, 7, 1) . substr($string, 6, 1) .
            substr($string, 5, 1) . substr($string, 4, 1) .
   	    substr($string, 3, 1) . substr($string, 2, 1) .
	    substr($string, 1, 1) . substr($string, 0, 1);

      // Back to binary
      $dnum = unpack('d', $string);

      return $dnum[''];
   }


   // Converts a date string ( YYYY-MM-DD )( "2001-10-31" )
   // into bitwise ( YYYY YYYM MMMD DDDD )
   // Should only be used when saving
   function DateToInt16($date) {
      $YMD = explode('-', $date);
      settype($YMD[0], 'integer');
      settype($YMD[1], 'integer');
      settype($YMD[2], 'integer');
      return ((($YMD[0] - 1904) & 0x7F) << 9) |
             (($YMD[1] & 0x0f) << 5) |
	     ($YMD[2] & 0x1f);
   }


   // Converts a bitwise date ( YYYY YYYM MMMD DDDD )
   // Into the human readable date string ( YYYY-MM-DD )( "2001-2-28" )
   // Should only be used when loading
   function Int16ToDate($number) {
      settype($number, 'integer');
      $year = ($number >> 9) & 0x7F;
      $year += 1904;
      $month = ($number >> 5) & 0xF;
      $day = $number & 0x1F;
      return $year . '-' . $month . '-' . $day;
   }


   // Converts a string into hexadecimal.
   // If $maxLen is specified and is greater than zero, the string is
   // trimmed and will contain up to $maxLen characters.
   // String("abcd", 2) will return "ab" hex encoded (a total of 4
   // resulting bytes, but 2 encoded characters).
   // Returned string is *not* NULL-terminated.
   function String($value, $maxLen = false) {
      $value = bin2hex($value);
      if ($maxLen !== false && $maxLen > 0)
         $value = substr($value, 0, $maxLen * 2);
      return $value;
   }


   // Pads a hex-encoded value (typically a string) to a fixed size.
   // May grow too long if $value starts too long
   // $value = hex encoded value
   // $minLen = Append nulls to $value until it reaches $minLen
   // $minLen is the desired size of the string, unencoded.
   // PadString('6162', 3) results in '616200' (remember the hex encoding)
   function PadString($value, $minLen) {
      $PadBytes = '00000000000000000000000000000000';
      $PadMe = $minLen - (strlen($value) / 2);
      while ($PadMe > 0) {
         if ($PadMe > 16)
	    $value .= $PadBytes;
	 else
	    return $value . substr($PadBytes, 0, $PadMe * 2);

	 $PadMe = $minLen - (strlen($value) / 2);
      }

      return $value;
   }


   /*
    * Record manipulation functions
    */

   // Sets the current record pointer to the new record number if an
   // argument is passed in.
   // Returns the old record number (just in case you want to jump back)
   // Does not do basic record initialization if we are going to a new
   // record.
   function GoToRecord($num = false) {
      if ($num === false)
         return $this->CurrentRecord;
      if (gettype($num) == 'string' && ($num[0] == '+' || $num[0] == '-'))
         $num = $this->CurrentRecord + $num;
      $oldRecord = $this->CurrentRecord;
      $this->CurrentRecord = $num;
      return $oldRecord;
   }


   // Returns the size of the current record if no arguments.
   // Returns the size of the specified record if arguments.
   function GetRecordSize($num = false) {
     if ($num === false)
         $num = $this->CurrentRecord;
      if (! isset($this->Records[$num]))
         return 0;
      return strlen($this->Records[$num]) / 2;
   }


   // Adds to the current record.  The input data must be already
   // hex encoded.  Initializes the record if it doesn't exist.
   function AppendCurrent($value) {
      if (! isset($this->Records[$this->CurrentRecord]))
         $this->Records[$this->CurrentRecord] = '';
      $this->Records[$this->CurrentRecord] .= $value;
   }


   // Adds a byte to the current record
   function AppendInt8($value) {
      $this->AppendCurrent($this->Int8($value));
   }


   // Adds an integer (2 bytes) to the current record
   function AppendInt16($value) {
      $this->AppendCurrent($this->Int16($value));
   }


   // Adds an integer (4 bytes) to the current record
   function AppendInt32($value) {
      $this->AppendCurrent($this->Int32($value));
   }


   // Adds a double to the current record
   function AppendDouble($value) {
      $this->AppendCurrent($this->Double($value));
   }


   // Adds a string (not NULL-terminated)
   function AppendString($value, $maxLen = false) {
      $this->AppendCurrent($this->String($value, $maxLen));
   }


   // Returns true if the specified/current record exists and is set
   function RecordExists($Rec = false) {
      if ($Rec === false)
         $Rec = $this->CurrentRecord;
      if (isset($this->Records[$Rec]))
         return true;
      return false;
   }


   // Returns the hex-encoded data for the specified record or the current
   // record if not specified
   // This is nearly identical to GetRecordRaw except that this function
   // may be overridden by classes (see modules/doc.inc) and that there
   // should always be a function that will return the raw data of the
   // Records array.
   function GetRecord($Rec = false) {
      if ($Rec === false)
         $Rec = $this->CurrentRecord;
      if (isset($this->Records[$Rec]))
         return $this->Records[$Rec];
      return '';
   }


   // Returns the attributes for the specified record or the current
   // record if not specified.
   function GetRecordAttrib($Rec = false) {
      if ($Rec === false)
         $Rec = $this->CurrentRecord;
      if (isset($this->RecordAttrs[$Rec]))
         return $this->RecordAttrs[$Rec] & PDB_RECORD_ATTRIB_CATEGORY_MASK;
      return 0;
   }


   // Returns the raw data inside the current/specified record.  Use this
   // for odd record types (like a Datebook record).  Also, use this
   // instead of just using $PDB->Records[] directly.
   // Please do not override this function.
   function GetRecordRaw($Rec = false) {
      if ($Rec === false)
         $Rec = $this->CurrentRecord;
      if (isset($this->Records[$Rec]))
         return $this->Records[$Rec];
      return false;
   }


   // Sets the hex-encoded data (or whatever) for the current record
   // Use this instead of the Append* functions if you have an odd
   // type of record (like a Datebook record).
   // Also, use this instead of just setting $PDB->Records[]
   // directly.
   // SetRecordRaw('data');
   // SetRecordRaw(24, 'data');   (specifying the record num)
   function SetRecordRaw($A, $B = false) {
      if ($B === false) {
         $B = $A;
	 $A = $this->CurrentRecord;
      }
      $this->Records[$A] = $B;
   }


   // Sets the attributes for the specified record or the current
   // record if not specified.
   // Note:  The 'attributes' byte also sets the category.
   // SetRecordAttrib($attr);
   // SetRecordAttrib($RecNo, $attr);
   function SetRecordAttrib($A, $B = false) {
      if ($B === false) {
         $B = $A;
	 $A = $this->CurrentRecord;
      }
      $this->RecordAttrs[$A] = $B & PDB_RECORD_ATTRIB_CATEGORY_MASK;
   }


   // Deletes the specified record or the current record if not specified.
   // If you delete the current record and then use an append function, the
   // record will be recreated.
   function DeleteRecord($RecNo = false) {
      if ($RecNo === false) {
         $RecNo = $this->CurrentRecord;
      }
      if (isset($this->Records[$RecNo]))
         unset($this->Records[$RecNo]);
      if (isset($this->RecordAttrs[$RecNo]))
         unset($this->RecordAttrs[$RecNo]);
   }


   // Returns an array of available record IDs in the order they should
   // be written.
   // Probably should only be called within the class.
   function GetRecordIDs() {
      $keys = array_keys($this->Records);
      if (! is_array($keys) || count($keys) < 1)
         return array();
      sort($keys, SORT_NUMERIC);
      return $keys;
   }


   // Returns the number of records.  This should match the number of
   // keys returned by GetRecordIDs().
   function GetRecordCount() {
      return count($this->Records);
   }


   // Returns the size of the AppInfo block.
   // Used only for writing
   function GetAppInfoSize() {
      if (! isset($this->AppInfo))
         return 0;
      return strlen($this->AppInfo) / 2;
   }


   // Returns the AppInfo block (hex encoded)
   // Used only for writing
   function GetAppInfo() {
      if (! isset($this->AppInfo))
         return 0;
      return $this->AppInfo;
   }


   // Returns the size of the SortInfo block
   // Used only for writing
   function GetSortInfoSize() {
      if (! isset($this->SortInfo))
         return 0;
      return strlen($this->SortInfo) / 2;
   }


   // Returns the SortInfo block (hex encoded)
   // Used only for writing
   function GetSortInfo() {
      if (! isset($this->SortInfo))
         return 0;
      return $this->SortInfo;
   }


   /*
    * Category Support
    *
    * If you plan on using categories in your module, you will have to use
    * these next four functions.
    *
    * In your LoadAppInfo(), you should have something like this ...
    *    function LoadAppInfo($fileData) {
    *       $this->LoadCategoryData($fileData);
    *       $fileData = substr($fileData, PDB_CATEGORY_SIZE);
    *       // .....
    *    }
    *
    * In your GetAppInfo() function, you need to output the categories ...
    *    function GetAppInfo() {
    *       $AppInfo = $this->CreateCategoryData();
    *       // .....
    *       return $AppInfo;
    *    }
    *
    * To change the category data, use SetCategoryList() and GetCategoryList()
    * helper functions.
    */

   // Returns the category data.  See SetCategoryList() for a description
   // of the format of the array returned.
   function GetCategoryList() {
      return $this->CategoryList;
   }


   // Sets the categories to what you specified.
   //
   // Data format:  (easy way)
   //    $categoryArray[###] = name
   // Or:  (how it is stored in the class)
   //    $categoryArray[###]['Name'] = name
   //    $categoryArray[###]['Renamed'] = true / false
   //    $categoryArray[###]['ID'] = number from 0 to 255
   //
   // Tips:
   //  * The number for the key of $categoryArray is from 0-15, specifying
   //    the order that the category is written in the AppInfo block.
   //  * I'd suggest numbering your categories sequentially
   //  * ID numbers must be unique.  If they are not, a new arbitrary number
   //    will be assigned.
   //  * $categoryArray[0] is reserved for the 'Unfiled' category.  It's
   //    ID is 0.  Do not use 0 as an index for the array.  Do not use 0 as
   //    an ID number.  This function will enforce this rule.
   //  * There is a maximum of 16 categories, including 'Unfiled'.  This
   //    means that you only have 15 left to play with.
   //
   // Category 0 is reserved for 'Unfiled'
   // Categories 1-127 are used for handheld ID numbers
   // Categories 128-255 are used for desktop ID numbers
   // Do not let category numbers be created larger than 255 -- this function
   // will erase categories with an ID larger than 255
   function SetCategoryList($list) {
      $usedCheck = 0;
      $usedList = array();

      // Clear out old category list
      $this->CategoryList = array();

      // Force category ID 0 to be "Unfiled"
      $list[0] = array('Name' => 'Unfiled', 'Renamed' => false, 'ID' => 0);

      $keys = array_keys($list);

      // Loop through the array
      $CatsWritten = 0;
      foreach ($keys as $key) {
         // If there is room for more categories ...
         if ($CatsWritten < PDB_CATEGORY_NUM && $key <= 15 && $key >= 0) {
	    if (is_array($list[$key]) && isset($list[$key]['ID']))
	       $id = $list[$key]['ID'];
	    else
	       $id = $key;

	    if ($id > 255 || isset($usedList[$id])) {
	       // Find a new arbitrary number for this category
	       $usedCheck ++;
	       while (isset($usedList[$usedCheck]))
	          $usedCheck ++;
	       $id = $usedCheck;
	    }

	    $CatsWritten ++;

	    // Set the "Renamed" flag if available
	    // By default, the Renamed flag is false
	    $RenamedFlag = false;
	    if (is_array($list[$key]) && isset($list[$key]['Renamed']) &&
		$list[$key]['Renamed'])
	       $RenamedFlag = true;

	    // Set the name of the category
	    $name = '';
	    if (is_array($list[$key])) {
	       if (isset($list[$key]['Name']))
	          $name = $list[$key]['Name'];
	    } else {
	       $name = $list[$key];
	    }

	    $this->CategoryList[$key] = array('Name' => $name,
	                                  'Renamed' => $RenamedFlag,
					  'ID' => $id);
	 }
      }
   }

   // Creates the hex-encoded data to be stuck in the AppInfo
   // block if the database supports categories.
   //
   // See SetCategoryList() for the format of $CategoryArray
   function CreateCategoryData() {
      $UsedIds = array();
      $UsedIdCheck = 0;

      // Force category data to be valid and in a specific format
      $this->SetCategoryList($this->CategoryList);

      $RenamedFlags = 0;
      $CategoryStr = '';
      $IdStr = '';
      $LastID = 0;

      foreach ($this->CategoryList as $key => $data) {
         $UsedIds[$data['ID']] = true;
	 if ($data['ID'] > $LastID)
	    $LastID = $data['ID'];
      }

      // Loop through the array
      for ($key = 0; $key < 16; $key ++) {
         if (isset($this->CategoryList[$key])) {
	    $RenamedFlags *= 2;

	    // Set the "Renamed" flag if available
	    // By default, the Renamed flag is false
	    if ($this->CategoryList[$key]['Renamed'])
	       $RenamedFlags += 1;

	    // Set the name of the category
	    $name = $this->CategoryList[$key]['Name'];
	    $name = $this->String($name, PDB_CATEGORY_NAME_LENGTH);
	    $CategoryStr .= $this->PadString($name,
	                                     PDB_CATEGORY_NAME_LENGTH);
	    $IdStr .= $this->Int8($this->CategoryList[$key]['ID']);
	 } else {
	    // Add blank categories where they are missing
	    $UsedIdCheck ++;
	    while (isset($UsedIds[$UsedIdCheck]))
	       $UsedIdCheck ++;
	    $RenamedFlags *= 2;
	    $CategoryStr .= $this->PadString('', PDB_CATEGORY_NAME_LENGTH);
	    $IdStr .= $this->Int8($UsedIdCheck);
	 }
      }

      // According to the docs, this is just the last ID written.  It doesn't
      // say whether this is the last one written by the palm, last one
      // written by the desktop, highest one written, or the ID for number
      // 15.
      $TrailingBytes = $this->Int8($LastID);
      $TrailingBytes .= $this->Int8(0);

      return $this->Int16($RenamedFlags) . $CategoryStr . $IdStr .
         $TrailingBytes;
   }


   // This should be called by other subclasses that use category support
   // It returns a category array.  Each element in the array is another
   // array with the key 'Name' set to the name of the category and
   // the key 'Renamed' set to the renamed flag for that category.
   function LoadCategoryData($fileData) {
      $key = 0;
      $RenamedFlags = $this->LoadInt16(substr($fileData, 0, 2));
      $Offset = 2;
      $StartingFlag = 65536;
      $Categories = array();
      while ($StartingFlag > 1) {
         $StartingFlag /= 2;
	 $Name = substr($fileData, $Offset, PDB_CATEGORY_NAME_LENGTH);
	 $i = 0;
	 while ($i < PDB_CATEGORY_NAME_LENGTH && $Name[$i] != "\0")
	    $i ++;
	 if ($i == 0)
	    $Name = '';
	 elseif ($i < PDB_CATEGORY_NAME_LENGTH)
	    $Name = substr($Name, 0, $i);
	 if ($RenamedFlags & $StartingFlag)
	    $RenamedFlag = true;
	 else
	    $RenamedFlag = false;
	 $Categories[$key] = array('Name' => $Name, 'Renamed' => $RenamedFlag);
	 $Offset += PDB_CATEGORY_NAME_LENGTH;
	 $key ++;
      }

      $CategoriesParsed = array();

      for ($key = 0; $key < 16; $key ++) {
         $UID = $this->LoadInt8(substr($fileData, $Offset, 1));
	 $Offset ++;
	 $CategoriesParsed[$key] = array('Name' => $Categories[$key]['Name'],
	                                 'Renamed' => $Categories[$key]['Renamed'],
					 'ID' => $UID);
      }

      // Ignore the last ID.  Maybe it should be preserved?
      $this->CategoryList = $CategoriesParsed;
   }


   /*
    * Database Writing Functions
    */

   // *NEW*
   // Takes a hex-encoded string and makes sure that when decoded, the data
   // lies on a four-byte boundary.  If it doesn't, it pads the string with
   // NULLs
   /*
    * Commented out because we don't use this function currently.
    * It is part of a test to see what is needed to get files to sync
    * properly with Desktop 4.0
    *
   function PadTo4ByteBoundary($string) {
      while ((strlen($string)/2) % 4) {
         $string .= '00';
      }
      return $string;
   }
    *
    */

   // Returns the hex encoded header of the pdb file
   // Header = name, attributes, version, creation/modification/backup
   //          dates, modification number, some offsets, record offsets,
   //          record attributes, appinfo block, sortinfo block
   // Shouldn't be called from outside the class
   function MakeHeader() {
      // 32 bytes = name, but only 31 available (one for null)
      $header = $this->String($this->Name, 31);
      $header = $this->PadString($header, 32);

      // Attributes & version fields
      $header .= $this->Int16($this->Attributes);
      $header .= $this->Int16($this->Version);

      // Creation, modification, and backup date
      if ($this->CreationTime != 0)
         $header .= $this->Int32($this->CreationTime + PDB_EPOCH_1904);
      else
         $header .= $this->Int32(time() + PDB_EPOCH_1904);
      if ($this->ModificationTime != 0)
         $header .= $this->Int32($this->ModificationTime + PDB_EPOCH_1904);
      else
         $header .= $this->Int32(time() + PDB_EPOCH_1904);
      if ($this->BackupTime != 0)
         $header .= $this->Int32($this->BackupTime + PDB_EPOCH_1904);
      else
         $header .= $this->Int32(0);

      // Calculate the initial offset
      $Offset = PDB_HEADER_SIZE + PDB_INDEX_HEADER_SIZE;
      $Offset += PDB_RECORD_HEADER_SIZE * count($this->GetRecordIDs());

      // Modification number, app information id, sort information id
      $header .= $this->Int32($this->ModNumber);

      $AppInfo_Size = $this->GetAppInfoSize();
      if ($AppInfo_Size > 0) {
         $header .= $this->Int32($Offset);
	 $Offset += $AppInfo_Size;
      } else
         $header .= $this->Int32(0);

      $SortInfo_Size = $this->GetSortInfoSize();
      if ($SortInfo_Size > 0) {
         $header .= $this->Int32($Offset);
         $Offset += $SortInfo_Size;
      } else
         $header .= $this->Int32(0);

      // Type, creator
      $header .= $this->String($this->TypeID, 4);
      $header .= $this->String($this->CreatorID, 4);

      // Unique ID seed
      $header .= $this->Int32(0);

      // next record list
      $header .= $this->Int32(0);

      // Number of records
      $header .= $this->Int16($this->GetRecordCount());

      // Compensate for the extra 2 NULL characters in the $Offset
      $Offset += 2;

      // Dump each record
      if ($this->GetRecordCount() != 0) {
         $keys = $this->GetRecordIDs();
	 sort($keys, SORT_NUMERIC);
	 foreach ($keys as $index) {
	    $header .= $this->Int32($Offset);
	    $header .= $this->Int8($this->GetRecordAttrib($index));

	    // The unique id is just going to be the record number
	    $header .= $this->Int24($index);

	    $Offset += $this->GetRecordSize($index);
	    // *new* method 3
	    //$Mod4 = $Offset % 4;
	    //if ($Mod4)
	    //   $Offset += 4 - $Mod4;
	 }
      }

      // AppInfo and SortInfo blocks go here
      if ($AppInfo_Size > 0)
         // *new* method 1
         $header .= $this->GetAppInfo();
         //$header .= $this->PadTo4ByteBoundary($this->GetAppInfo());

      if ($SortInfo_Size > 0)
         // *new* method 2
         $header .= $this->GetSortInfo();
         //$header .= $this->PadTo4ByteBoundary($this->GetSortInfo());

      // These are the mysterious two NULL characters that we need
      $header .= $this->Int16(0);

      return $header;
   }


   // Writes the database to the file handle specified.
   // Use this function like this:
   //   $file = fopen("output.pdb", "wb");
   //   // "wb" = write binary for non-Unix systems
   //   if (! $file) {
   //      echo "big problem -- can't open file";
   //      return;
   //   }
   //   $pdb->WriteToFile($file);
   //   fclose($file);
   function WriteToFile($file) {
      $header = $this->MakeHeader();
      fwrite($file, pack('H*', $header), strlen($header) / 2);
      $keys = $this->GetRecordIDs();
      sort($keys, SORT_NUMERIC);
      foreach ($keys as $index) {
         // *new* method 3
         //$data = $this->PadTo4ByteBoundary($this->GetRecord($index));
         $data = $this->GetRecord($index);
	 fwrite($file, pack('H*', $data), strlen($data) / 2);
      }
      fflush($file);
   }


   // Writes the database to the standard output (like echo).
   // Can be trapped with output buffering
   function WriteToStdout() {
      // You'd think these three lines would work.
      // If someone can figure out why they don't, please tell me.
      //
      // $fp = fopen('php://stdout', 'wb');
      // $this->WriteToFile($fp);
      // fclose($fp);

      $header = $this->MakeHeader();
      echo pack("H*", $header);
      $keys = $this->GetRecordIDs();
      sort($keys, SORT_NUMERIC);
      foreach ($keys as $index) {
         // *new* method 3
	 $data = $this->GetRecord($index);
         //$data = $this->PadTo4ByteBoundary($this->GetRecord($index));
	 echo pack("H*", $data);
      }
   }


   // Writes the database to the standard output (like echo) but also
   // writes some headers so that the browser should prompt to save the
   // file properly.
   //
   // Use this only if you didn't send any content and you only want the
   // PHP script to output the PDB file and nothing else.  An example
   // would be if you wanted to have 'download' link so the user can
   // stick the information they are currently viewing and transfer
   // it easily into their handheld.
   //
   // $filename is the desired filename to download the database as.
   // For example, DownloadPDB('memos.pdb');
   function DownloadPDB($filename) {

      // Alter the filename to only allow certain characters.
      // Some platforms and some browsers don't respond well if
      // there are illegal characters (such as spaces) in the name of
      // the file being downloaded.
      $filename = preg_replace('/[^-a-zA-Z0-9\\.]/', '_', $filename);

      if (strstr($_SERVER['HTTP_USER_AGENT'], 'compatible; MSIE ') !== false &&
          strstr($_SERVER['HTTP_USER_AGENT'], 'Opera') === false) {
	 // IE doesn't properly download attachments.  This should work
	 // pretty well for IE 5.5 SP 1
	 header("Content-Disposition: inline; filename=$filename");
	 header("Content-Type: application/download; name=\"$filename\"");
      } else {
         // Use standard headers for Netscape, Opera, etc.
	 header("Content-Disposition: attachment; filename=\"$filename\"");
	 header("Content-Type: application/x-pilot; name=\"$filename\"");
      }

      $this->WriteToStdout();
   }


   /*
    * Loading in a database
    */

   // Reads data from the file and tries to load it properly
   // $file is the already-opened file handle.
   // Returns false if no error
   function ReadFile($file) {
      // 32 bytes = name, but only 31 available
      $this->Name = fread($file, 32);

      $i = 0;
      while ($i < 32 && $this->Name[$i] != "\0")
         $i ++;
      $this->Name = substr($this->Name, 0, $i);

      $this->Attributes = $this->LoadInt16($file);
      $this->Version = $this->LoadInt16($file);

      $this->CreationTime = $this->LoadInt32($file);
      if ($this->CreationTime != 0)
         $this->CreationTime -= PDB_EPOCH_1904;
      if ($this->CreationTime < 0)
         $this->CreationTime = 0;

      $this->ModificationTime = $this->LoadInt32($file);
      if ($this->ModificationTime != 0)
         $this->ModificationTime -= PDB_EPOCH_1904;
      if ($this->ModificationTime < 0)
         $this->ModificationTime = 0;

      $this->BackupTime = $this->LoadInt32($file);
      if ($this->BackupTime != 0)
         $this->BackupTime -= PDB_EPOCH_1904;
      if ($this->BackupTime < 0)
         $this->BackupTime = 0;

      // Modification number
      $this->ModNumber = $this->LoadInt32($file);

      // AppInfo and SortInfo size
      $AppInfoOffset = $this->LoadInt32($file);
      $SortInfoOffset = $this->LoadInt32($file);

      // Type, creator
      $this->TypeID = fread($file, 4);
      $this->CreatorID = fread($file, 4);

      // Skip unique ID seed
      fread($file, 4);

      // skip next record list (hope that's ok)
      fread($file, 4);

      $RecCount = $this->LoadInt16($file);

      $RecordData = array();

      while ($RecCount > 0) {
         $RecCount --;
	 $Offset = $this->LoadInt32($file);
	 $Attrs = $this->LoadInt8($file);
	 $UID = $this->LoadInt24($file);
	 $RecordData[] = array('Offset' => $Offset, 'Attrs' => $Attrs,
	                       'UID' => $UID);
      }

      // Create the offset list
      if ($AppInfoOffset != 0)
         $OffsetList[$AppInfoOffset] = 'AppInfo';
      if ($SortInfoOffset != 0)
         $OffsetList[$SortInfoOffset] = 'SortInfo';
      foreach ($RecordData as $data)
         $OffsetList[$data['Offset']] = array('Record', $data);
      fseek($file, 0, SEEK_END);
      $OffsetList[ftell($file)] = 'EOF';

      // Parse each chunk
      ksort($OffsetList);
      $Offsets = array_keys($OffsetList);
      while (count($Offsets) > 1) {
         // Don't use the EOF (which should be the last offset)
	 $ThisOffset = $Offsets[0];
	 $NextOffset = $Offsets[1];
	 if ($OffsetList[$ThisOffset] == 'EOF')
	    // Messed up file.  Stop here.
	    return true;
	 $FuncName = 'Load';
	 if (is_array($OffsetList[$ThisOffset])) {
	    $FuncName .= $OffsetList[$ThisOffset][0];
	    $extraData = $OffsetList[$ThisOffset][1];
	 } else {
	    $FuncName .= $OffsetList[$ThisOffset];
	    $extraData = false;
	 }
	 fseek($file, $ThisOffset);
	 $fileData = fread($file, $NextOffset - $ThisOffset);
	 if ($this->$FuncName($fileData, $extraData))
	    return -2;
	 array_shift($Offsets);
      }

      return false;
   }


   // Generic function to load the AppInfo block into $this->AppInfo
   // Should only be called within this class
   // Return false to signal no error
   function LoadAppInfo($fileData) {
      $this->AppInfo = bin2hex($fileData);
      return false;
   }


   // Generic function to load the SortInfo block into $this->SortInfo
   // Should only be called within this class
   // Return false to signal no error
   function LoadSortInfo($fileData) {
      $this->SortInfo = bin2hex($fileData);
      return false;
   }


   // Generic function to load a record
   // Should only be called within this class
   // Return false to signal no error
   function LoadRecord($fileData, $recordInfo) {
      $this->Records[$recordInfo['UID']] = bin2hex($fileData);
      $this->RecordAttrs[$recordInfo['UID']] = $recordInfo['Attrs'];
      return false;
   }
}
?>