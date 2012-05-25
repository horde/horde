#!/usr/bin/php -q
<?php
function analysedir( $path_, $list_ )
{
  // Read dir
  $handle = opendir($path_);
  while (false !== ($file = readdir($handle)))
    {
      if( $file!='.' && $file!='..')
	{
	  $file = $path_ . DIRECTORY_SEPARATOR . $file;
	  //echo "$filen";
 
	  if( !is_dir( $file ) )  // If File: Append
	    {
	      $list_[count($list_)]=$file;
	    }
	  else   // If Folder: scan recursively
	    {
	      $list_ += analysedir($file, $list_ );
	    }
	}
    }      // While END
  return $list_;
}
 
function substitute_skeleton( $filename, $modulname )
{
  $prjUC=strtoupper(trim($modulname));
  $prjLC=strtolower($prjUC);
  $prjMC=substr($prjUC, 0, 1) . substr($prjLC, 1, strlen($prjLC)-1);
 
  $filehandle=fopen(trim($filename), 'r');
  $file=fread($filehandle, filesize($filename));
  fclose($filehandle);
  $newfile=str_replace(array('SKELETON', 'Skeleton', 'skeleton'), array($prjUC, $prjMC, $prjLC), $file);
  $filehandle=fopen(trim($filename), 'w');
  fwrite($filehandle, $newfile);
  fclose($filehandle);
}
 
function help()
{
  echo "projectrename.php </path/to/skeleton-folder/> <modulname>n";
}
 
//
// ------------------- Main-Code --------------------
//
 
if (count($_SERVER['argv'])==3)
  {
    // Preparation
    $list = array();
    $path = trim($_SERVER['argv'][1]);
    $modul = trim($_SERVER['argv'][2]);
 
    // Fetch Filelist
    $list = analysedir( $path, $list );
 
    // Modify each File
    foreach( $list as $file )
      {
        //echo $modul.": ".$file."n";
        substitute_skeleton( $file, $modul );
      }
  }
else
  help();
?>