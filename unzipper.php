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
 * @version 0.0.2 Alpha
 */

$timestart = microtime(TRUE);

$arc = new Unzipper;

$timeend = microtime(TRUE);
$time = $timeend - $timestart;

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

      if(!empty($this->zipfiles)) {
        self::$status = '.zip or .gz files found, ready for extraction';
      }
      else {
        self::$status = '<span style="color:red; font-weight:bold;font-size:120%;">Error: No .zip or .gz files found.</span>';
      }
    }

    //check if an archive was selected for unzipping
    //check if archive has been selected
    $input = '';
    $input = strip_tags($_POST['zipfile']);

    //allow only local existing archives to extract
    if ($input !== '') {
      if (in_array($input, $this->zipfiles)) {
        self::extract($input, $this->localdir);
      }
    }
  }

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
    if(!class_exists('ZipArchive')) {
      self::$status = '<span style="color:red; font-weight:bold;font-size:120%;">Error: Your PHP version does not support unzip functionality.</span>';
      return;
    }

    $zip = new ZipArchive;

    // Check if archive is readable.
    if ($zip->open($archive) === TRUE) {
      // Check if destination is writable
      if(is_writeable($destination . '/')) {
        $zip->extractTo($destination);
        $zip->close();
        self::$status = '<span style="color:green; font-weight:bold;font-size:120%;">Files unzipped successfully</span>';
      }
      else {
        self::$status = '<span style="color:red; font-weight:bold;font-size:120%;">Error: Directory not writeable by webserver.</span>';
      }
    }
    else {
      self::$status = '<span style="color:red; font-weight:bold;font-size:120%;">Error: Cannot read .zip archive.</span>';
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
    if(!function_exists('gzopen')) {
      self::$status = '<span style="color:red; font-weight:bold;font-size:120%;">Error: Your PHP has no zlib support enabled.</span>';
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
    if(file_exists($destination . '/' . $filename)) {
      self::$status = '<span style="color:green; font-weight:bold;font-size:120%;">File unzipped successfully.</span>';
    }
    else {
      self::$status = '<span style="color:red; font-weight:bold;font-size:120%;">Error unzipping file.</span>';
    }

  }
}

?>

<!DOCTYPE html>
<head>
  <title>File Unzipper</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style type="text/css">
    <!--
    body {
      font-family: Arial, serif;
      line-height: 150%;
    }

    fieldset {
      border: 0px solid #000;
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

    .submit {
      -moz-box-shadow: inset 0px 1px 0px 0px #bbdaf7;
      -webkit-box-shadow: inset 0px 1px 0px 0px #bbdaf7;
      box-shadow: inset 0px 1px 0px 0px #bbdaf7;
      background: -webkit-gradient(linear, left top, left bottom, color-stop(0.05, #79bbff), color-stop(1, #378de5));
      background: -moz-linear-gradient(center top, #79bbff 5%, #378de5 100%);
      filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#79bbff', endColorstr='#378de5');
      background-color: #79bbff;
      -moz-border-radius: 4px;
      -webkit-border-radius: 4px;
      border-radius: 4px;
      border: 1px solid #84bbf3;
      display: inline-block;
      color: #ffffff;
      font-family: arial;
      font-size: 15px;
      font-weight: bold;
      padding: 10px 24px;
      text-decoration: none;
      text-shadow: 1px 1px 0px #528ecc;
    }

    .submit:hover {
      background: -webkit-gradient(linear, left top, left bottom, color-stop(0.05, #378de5), color-stop(1, #79bbff));
      background: -moz-linear-gradient(center top, #378de5 5%, #79bbff 100%);
      filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#378de5', endColorstr='#79bbff');
      background-color: #378de5;
    }

    .submit:active {
      position: relative;
      top: 1px;
    }

    /* This imageless css button was generated by CSSButtonGenerator.com */

    -->
  </style>
</head>

<body>
<h1>Archive Unzipper</h1>

<p>Select .zip archive or .gz file you want to extract:</p>

<form action="" method="POST">
  <fieldset>

    <select name="zipfile" size="1" class="select">
      <?php foreach ($arc->zipfiles as $zip) {
        echo "<option>$zip</option>";
      }
      ?>
    </select>

    <br/>

    <input type="submit" name="submit" class="submit" value="Unzip Archive"/>

  </fieldset>
</form>
<p class="status">
  Status: <?php echo $arc::$status; ?>
  <br/>
  Processingtime: <?php echo $time; ?> ms
</p>
</body>
</html>