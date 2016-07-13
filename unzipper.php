<?php
/**
 * The Unzipper extracts .zip archives and .gz files on webservers. It's handy if you
 * do not have shell access. E.g. if you want to upload a lot of files
 * (php framework or image collection) as archive to save time.
 *
 *
 * @author  Andreas Tasch, at[tec], attec.at
 * @license GNU GPL v3
 * @package attec.toolbox
 * @version 0.0.3 Beta
 */

$timestart = microtime(TRUE);

$arc = new Unzipper;

$timeend = microtime(TRUE);
$time = $timeend - $timestart;

/**
 * Class Unzipper
 */
class Unzipper {
  public $localdir = '.';
  public $zipfiles = array();
  public static $status = '';

  public function __construct() {

    //read directory and pick .zip and .gz files
    if ($dh = opendir($this->localdir)) {
      while (($file = readdir($dh)) !== FALSE) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'zip'
          || pathinfo($file, PATHINFO_EXTENSION) === 'gz'
        ) {
          $this->zipfiles[] = $file;
        }
      }
      closedir($dh);

      if (!empty($this->zipfiles)) {
        self::$status = '.zip or .gz files found, ready for extraction';
      }
      else {
        self::$status = '<span class="status--ERROR">Error: No .zip or .gz files found.</span>';
      }
    }

    //check if an archive was selected for unzipping
    //check if archive has been selected
    $input = isset($_POST['zipfile']) ? strip_tags($_POST['zipfile']) : '';
    $destination = isset($_POST['extpath']) ? strip_tags($_POST['extpath']) : '';
    //allow only local existing archives to extract
    if ($input !== '') {
      if (empty($destination)) {
        $extpath = $this->localdir;
      }
      else {
        $extpath = $this->localdir . '/' . $destination;
        if (!is_dir($extpath)) {
          mkdir($extpath);
        }
      }
      if (in_array($input, $this->zipfiles)) {
        self::extract($input, $extpath);
      }
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
    if ($ext === 'zip') {
      self::extractZipArchive($archive, $destination);
    }
    else {
      if ($ext === 'gz') {
        self::extractGzipFile($archive, $destination);
      }
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
      self::$status = '<span class="status--ERROR">Error: Your PHP version does not support unzip functionality.</span>';
      return;
    }

    $zip = new ZipArchive;

    // Check if archive is readable.
    if ($zip->open($archive) === TRUE) {
      // Check if destination is writable
      if (is_writeable($destination . '/')) {
        $zip->extractTo($destination);
        $zip->close();
        self::$status = '<span class="status--OK">Files unzipped successfully</span>';
      }
      else {
        self::$status = '<span class="status--ERROR">Error: Directory not writeable by webserver.</span>';
      }
    }
    else {
      self::$status = '<span class="status--ERROR">Error: Cannot read .zip archive.</span>';
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
      self::$status = '<span class="status--ERROR">Error: Your PHP has no zlib support enabled.</span>';
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
      self::$status = '<span class="status--OK">File unzipped successfully.</span>';
    }
    else {
      self::$status = '<span class="status--ERROR">Error unzipping file.</span>';
    }

  }
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>File Unzipper</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style type="text/css">
    <!--
    body {
      font-family: Arial, sans-serif;
      line-height: 150%;
    }

    label {
      display: block;
      margin-top: 20px;
    }

    fieldset {
      border: 0;
    }

    .select {
      padding: 5px;
      font-size: 110%;
    }

    .status {
      margin-top: 20px;
      padding: 5px;
      font-size: 80%;
      background: #EEE;
      border: 1px dotted #DDD;
    }

    .status--ERROR {
      color: red;
      font-weight: bold;
      font-size: 120%;
    }

    .status--OK {
      color: green;
      font-weight: bold;
      font-size: 120%
    }

    .form-field {
      border: 1px solid #AAA;
      padding: 8px;
      width: 280px;
    }

    .info {
      margin-top: 0;
      font-size: 80%;
      color: #777;
    }

    .submit {
      background-color: #378de5;
      border: 0;
      color: #ffffff;
      font-size: 15px;
      padding: 10px 24px;
      margin: 20px 0 20px 0;
      text-decoration: none;
    }

    .submit:hover {
      background-color: #2c6db2;
      cursor: pointer;
    }
    -->
  </style>
</head>
<body>
<h1>Archive Unzipper</h1>
<form action="" method="POST">
  <fieldset>
    <label for="zipfile">Select .zip archive or .gz file you want to extract:</label>
    <select name="zipfile" size="1" class="select">
      <?php foreach ($arc->zipfiles as $zip) {
        echo "<option>$zip</option>";
      }
      ?>
    </select>
    <label for="extpath">Extraction path (optional):</label>
    <input type="text" name="extpath" class="form-field"
           placeholder="mypath"/>
    <p class="info">Enter extraction path without leading or trailing slashes (e.g. "mypath"). If left blank current directory will be used.</p>
    <input type="submit" name="submit" class="submit" value="Unzip Archive"/>
  </fieldset>
</form>
<p class="status">
  Status: <?php echo $arc::$status; ?> <br/>
  Processing Time: <?php echo $time; ?> ms
</p>
</body>
</html>
