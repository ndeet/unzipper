# The Unzipper

The Unzipper extracts .zip and .rar archives or .gz files on webservers. It detects .zip/.rar/.gz archives and let you choose which one to extract (if there are multiple archives available).
As of version 0.1.0 it also supports creating archives.

It's handy if you do not have shell access. E.g. if you want to upload a lot of files (php framework or image collection) as archive - because it is much faster than uploading each file by itself.


## Requirements    
PHP 5.3 and newer
(If you still run PHP < 5.6 you should consider updating PHP. These old PHP versions do not get any security updates and your site may be vulnerable.)


## Usage
* Download unzipper.php and place it in the same directory as your .zip archive or place it in the root directory as unzipper.php file can scan all the directores in a filesystem.
* In Browser type URL to unzipper.php

### Archive unzipper
* Choose .zip, .rar archive or .gz file you want to extract
* (Optional) select an extraction path, defaults to current directory
* Click "Unzip Archive"

### Archive zipper
* (Optional) Set path to zip, defaults to current directory
* Click "Zip Archive"

## Version
Beta version state, use at you own risk.


## License
Released under GNU/GPL v3


## Screenshot
![Screenshot of unzipper newer version](https://raw.githubusercontent.com/Chamuth/unzipper/master/images/unzipperscreenshot.png)


## Updates    
Get latest code at https://github.com/ndeet/unzipper


## Credits   
[See contributors on Github](https://github.com/ndeet/unzipper/graphs/contributors)  
