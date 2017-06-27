<?php
/**
 * The Unzipper extracts .zip or .rar archives and .gz files on webservers.
 * It's handy if you do not have shell access. E.g. if you want to upload a lot
 * of files (php framework or image collection) as an archive to save time.
 * As of version 0.1.0 it also supports creating archives.
 *
 * @author  Andreas Tasch, at[tec], attec.at
 * @license GNU GPL v3
 * @package attec.toolbox
 * @version 0.1.0
 */
define('VERSION', '0.1.0');

$timestart = microtime(TRUE);
$GLOBALS['status'] = array();

$unzipper = new Unzipper;
if (isset($_POST['dounzip'])) {
  //check if an archive was selected for unzipping
  $archive = isset($_POST['zipfile']) ? strip_tags($_POST['zipfile']) : '';
  $destination = isset($_POST['extpath']) ? strip_tags($_POST['extpath']) : '';
  $unzipper->prepareExtraction($archive, $destination);
}

if (isset($_POST['dozip'])) {
  $zippath = !empty($_POST['zippath']) ? strip_tags($_POST['zippath']) : '.';
  // Resulting zipfile e.g. zipper--2016-07-23--11-55.zip
  $zipfile = 'zipper-' . date("Y-m-d--H-i") . '.zip';
  Zipper::zipDir($zippath, $zipfile);
}

$timeend = microtime(TRUE);
$time = $timeend - $timestart;

function scanFiles($localdir)
{
   $result = array();

   foreach (scandir($localdir) as $file)
   {
      if ($file != "." && $file != ".."){
        $filename = $localdir . "/" . $file;

        if (is_dir($filename))
          $result = array_merge($result, scanFiles($filename));
        else
        {
          if (pathinfo($filename, PATHINFO_EXTENSION) === 'zip'
            || pathinfo($filename, PATHINFO_EXTENSION) === 'gz'
            || pathinfo($filename, PATHINFO_EXTENSION) === 'rar')
          {
            $result[] = $filename;
          }
        }
      }
   }

   return $result;
}
/**
 * Class Unzipper
 */
class Unzipper {
  public $localdir = '.';
  public $zipfiles = array();

  public function __construct() {
    
      $this->zipfiles = scanFiles($this->localdir);

      if (!empty($this->zipfiles)) {
        $GLOBALS['status'] = array('info' => '.zip or .gz or .rar files found, ready for extraction');
      }
      else {
        $GLOBALS['status'] = array('info' => 'No .zip or .gz or rar files found. So only zipping functionality available.');
      }
    
  }

  /**
   * Prepare and check zipfile for extraction.
   *
   * @param $archive
   * @param $destination
   */
  public function prepareExtraction($archive, $destination) {
    // Determine paths.
    if (empty($destination)) {
      $extpath = $this->localdir;
    }
    else {
      $extpath = $this->localdir . '/' . $destination;
      // todo move this to extraction function
      if (!is_dir($extpath)) {
        mkdir($extpath);
      }
    }
    //allow only local existing archives to extract
    if (in_array($archive, $this->zipfiles)) {
      self::extract($archive, $extpath);
    }
  }

  /**
   * Checks file extension and calls suitable extractor functions.
   *
   * @param $archive
   * @param $destination
   */
  public static function extract($archive, $destination) {
    $ext = pathinfo($archive, PATHINFO_EXTENSION);
    switch ($ext) {
      case 'zip':
        self::extractZipArchive($archive, $destination);
        break;
      case 'gz':
        self::extractGzipFile($archive, $destination);
        break;
      case 'rar':
        self::extractRarArchive($archive, $destination);
        break;
    }

  }

  /**
   * Decompress/extract a zip archive using ZipArchive.
   *
   * @param $archive
   * @param $destination
   */
  public static function extractZipArchive($archive, $destination) {
    // Check if webserver supports unzipping.
    if (!class_exists('ZipArchive')) {
      $GLOBALS['status'] = array('error' => 'Error: Your PHP version does not support unzip functionality.');
      return;
    }

    $zip = new ZipArchive;

    // Check if archive is readable.
    if ($zip->open($archive) === TRUE) {
      // Check if destination is writable
      if (is_writeable($destination . '/')) {
        $zip->extractTo($destination);
        $zip->close();
        $GLOBALS['status'] = array('success' => 'Files unzipped successfully');
      }
      else {
        $GLOBALS['status'] = array('error' => 'Error: Directory not writeable by webserver.');
      }
    }
    else {
      $GLOBALS['status'] = array('error' => 'Error: Cannot read .zip archive.');
    }
  }

  /**
   * Decompress a .gz File.
   *
   * @param $archive
   * @param $destination
   */
  public static function extractGzipFile($archive, $destination) {
    // Check if zlib is enabled
    if (!function_exists('gzopen')) {
      $GLOBALS['status'] = array('error' => 'Error: Your PHP has no zlib support enabled.');
      return;
    }

    $filename = pathinfo($archive, PATHINFO_FILENAME);
    $gzipped = gzopen($archive, "rb");
    $file = fopen($filename, "w");

    while ($string = gzread($gzipped, 4096)) {
      fwrite($file, $string, strlen($string));
    }
    gzclose($gzipped);
    fclose($file);

    // Check if file was extracted.
    if (file_exists($destination . '/' . $filename)) {
      $GLOBALS['status'] = array('success' => 'File unzipped successfully.');
    }
    else {
      $GLOBALS['status'] = array('error' => 'Error unzipping file.');
    }

  }

  /**
   * Decompress/extract a Rar archive using RarArchive.
   *
   * @param $archive
   * @param $destination
   */
  public static function extractRarArchive($archive, $destination) {
    // Check if webserver supports unzipping.
    if (!class_exists('RarArchive')) {
      $GLOBALS['status'] = array('error' => 'Error: Your PHP version does not support .rar archive functionality. <a class="info" href="http://php.net/manual/en/rar.installation.php" target="_blank">How to install RarArchive</a>');
      return;
    }
    // Check if archive is readable.
    if ($rar = RarArchive::open($archive)) {
      // Check if destination is writable
      if (is_writeable($destination . '/')) {
        $entries = $rar->getEntries();
        foreach ($entries as $entry) {
          $entry->extract($destination);
        }
        $rar->close();
        $GLOBALS['status'] = array('success' => 'Files extracted successfully.');
      }
      else {
        $GLOBALS['status'] = array('error' => 'Error: Directory not writeable by webserver.');
      }
    }
    else {
      $GLOBALS['status'] = array('error' => 'Error: Cannot read .rar archive.');
    }
  }

}

/**
 * Class Zipper
 *
 * Copied and slightly modified from http://at2.php.net/manual/en/class.ziparchive.php#110719
 * @author umbalaconmeogia
 */
class Zipper {
  /**
   * Add files and sub-directories in a folder to zip file.
   *
   * @param string     $folder
   *   Path to folder that should be zipped.
   *
   * @param ZipArchive $zipFile
   *   Zipfile where files end up.
   *
   * @param int        $exclusiveLength
   *   Number of text to be exclusived from the file path.
   */
  private static function folderToZip($folder, &$zipFile, $exclusiveLength) {
    $handle = opendir($folder);

    while (FALSE !== $f = readdir($handle)) {
      // Check for local/parent path or zipping file itself and skip.
      if ($f != '.' && $f != '..' && $f != basename(__FILE__)) {
        $filePath = "$folder/$f";
        // Remove prefix from file path before add to zip.
        $localPath = substr($filePath, $exclusiveLength);

        if (is_file($filePath)) {
          $zipFile->addFile($filePath, $localPath);
        }
        elseif (is_dir($filePath)) {
          // Add sub-directory.
          $zipFile->addEmptyDir($localPath);
          self::folderToZip($filePath, $zipFile, $exclusiveLength);
        }
      }
    }
    closedir($handle);
  }

  /**
   * Zip a folder (including itself).
   * Usage:
   *   Zipper::zipDir('path/to/sourceDir', 'path/to/out.zip');
   *
   * @param string $sourcePath
   *   Relative path of directory to be zipped.
   *
   * @param string $outZipPath
   *   Relative path of the resulting output zip file.
   */
  public static function zipDir($sourcePath, $outZipPath) {
    $pathInfo = pathinfo($sourcePath);
    $parentPath = $pathInfo['dirname'];
    $dirName = $pathInfo['basename'];

    $z = new ZipArchive();
    $z->open($outZipPath, ZipArchive::CREATE);
    $z->addEmptyDir($dirName);
    if ($sourcePath == $dirName) {
      self::folderToZip($sourcePath, $z, 0);
    }
    else {
      self::folderToZip($sourcePath, $z, strlen("$parentPath/"));
    }
    $z->close();

    $GLOBALS['status'] = array('success' => 'Successfully created archive ' . $outZipPath);
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>File Unzipper + Zipper</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style type="text/css">
    <!--
    body {
      font-family: Arial, sans-serif;
      line-height: 150%;
      width:50%;
      background-color:#eee;
    }

    .container
    {
      position:absolute;
      left:50%;
      top:50%;
      transform:translateX(-50%) translateY(-50%);
    }

    .status .small
    {
      float:right;
      margin-right:20px;
    }

    label {
      display: block;
      margin-top: 20px;
    }

    fieldset {
      border: 0;
      background-color: #FFF;
      margin: 10px 0 25px 0;
      border-radius:10px;
      padding:10px 20px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);

      transition:height 1s;
    }

    .select {
      padding: 5px;
      font-size: 110%;
    }

    .status {
      margin:0;
      float:left;
      padding: 10px;
      font-size: 80%;
      background: #FFF;
      box-shadow: 0 -2px 1px dodgerblue;

      position:fixed;
      z-index:100;
      bottom:0;
      left:0;

      width:100%;

      animation: statusAnimation 1s;
    }

    @keyframes statusAnimation
    {
      0% { opacity: 0; transform:translateY(100%);}
      100% {opacity: 1; transform:translateY(0%);}
    }

    .status-text
    {
      font-weight:bold;
    }

    .status--ERROR {
      background-color: red;
      color: white;
      font-size: 100%;
    }

    .status--SUCCESS {
      background-color: dodgerblue;
      font-weight: bold;
      color: white;
      font-size: 100%;
    }

    .small {
      font-size: 0.7rem;
      font-weight: normal;
    }

    .version {
      font-size: 80%;
      text-align:center;
      margin-top:20px;
      color:gray
    }

    .form-field {
      border: 1px solid #AAA;
      padding: 8px;
      width: 280px;
      }

    .info {
      margin-top: 7px;
      font-size: 80%;
      color: #777;
    }

    .submit {
      background-color: dodgerblue;
      border: 0;
      color: #ffffff;
      font-size: 15px;
      border-radius:5px;
      padding: 10px 24px;
      margin: 20px 0 20px 0;
      text-decoration: none;
      font-weight:bold;
      border:solid 2px dodgerblue;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);

      transition: background-color 1s, color 1s, box-shadow 1s;
    }

    .submit:hover {
      color: dodgerblue;
      background-color: #fff;
      cursor: pointer;
      box-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);

      transition: background-color 250ms, color 250ms, box-shadow 500ms
    }

    h1
    {
      margin-bottom:25px;
    }

    input[type="text"]:focus,
    select:focus,
    textarea:focus,
    button:focus {
        outline: none;
        border-color:dodgerblue;
        border-width:1.5px;

        transition: border 500ms;
    }

    input[type="text"],
    select,
    textarea
    {

      border-radius:5px;
      margin-top:5px;
      border-color:#AAA;
      border-width:1.5px;
      width: 100%; 
      box-sizing: border-box;
      -webkit-box-sizing:border-box;
      -moz-box-sizing: border-box;


      transition: border 500ms;
    }

    h1
    {
      text-transform:uppercase;
      font-size:15px;
      color:dodgerblue
    }


    @media screen and (max-width:1000px)
    {
      .container
      {
        width:90%;
      }

      .status .small
      {
        display:none;
      }
    }

    @media screen and (max-height:768px)
    {
      .status
      {
        position:fixed;
        width:auto;
        bottom:20px;
        left:20px;
        height:20px;
        border-radius:10px;

        box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);

        animation: statusAnimation 1s;  

        border:solid 2px dodgerblue;   

        transition: width 500ms;
      }

      .status .small
      {
        display:none;
      }

     
    }
    -->
  </style>
</head>
<body>
<p class="status status--<?php echo strtoupper(key($GLOBALS['status'])); ?>">
    <span class="status-text">Status: <?php echo reset($GLOBALS['status']); ?></span>
    <span class="small">Processing Time: <?= round($time, 5, PHP_ROUND_HALF_EVEN)?> seconds</span>
  </p>
  <div class="container">
  
  <form action="" method="POST">
    <fieldset>
      <h1>Archive Unzipper</h1>
      <label for="zipfile">Select .zip or .rar archive or .gz file you want to extract:</label>
      <select name="zipfile" size="1" class="select">
        <?php if (!empty($unzipper->zipfiles)) { ?>
          <?php foreach ($unzipper->zipfiles as $zip) {
            echo "<option>$zip</option>";
          }
        }else{
          ?><option value="" disabled selected>No archives found</option>
          <?php
        }
        ?>
      </select>
      <label for="extpath">Extraction path (optional):</label>
      <input type="text" name="extpath" class="form-field" />
      <p class="info">Enter extraction path without leading or trailing slashes (e.g. "mypath"). If left empty current directory will be used.</p>
      <input type="submit" name="dounzip" class="submit" value="Unzip Archive"/>
    </fieldset>

    <fieldset>
      <h1>Archive Zipper</h1>
      <label for="zippath">Path that should be zipped (optional):</label>
      <input type="text" name="zippath" class="form-field" />
      <p class="info">Enter path to be zipped without leading or trailing slashes (e.g. "zippath"). If left empty current directory will be used.</p>
      <input type="submit" name="dozip" class="submit" value="Zip Archive"/>
    </fieldset>
  </form>
  <p class="version">Unzipper version: <?php echo VERSION; ?></p>
  </div>
</body>
</html>
