<?php

//display error for DEV only
error_reporting(E_ALL);
ini_set('display_errors', '1');

// load composer autoload before load elFinder autoload If you need composer
//require './vendor/autoload.php';

// elFinder autoload
require './autoload.php';
// ===============================================

// Enable FTP connector netmount
elFinder::$netDrivers['ftp'] = 'FTP';
// ===============================================
/**
 * # Dropbox volume driver need `composer require dropbox-php/dropbox-php:dev-master@dev`
 *  OR "dropbox-php's Dropbox" and "PHP OAuth extension" or "PEAR's HTTP_OAUTH package"
 * * dropbox-php: http://www.dropbox-php.com/
 * * PHP OAuth extension: http://pecl.php.net/package/oauth
 * * PEAR's HTTP_OAUTH package: http://pear.php.net/package/http_oauth
 *  * HTTP_OAUTH package require HTTP_Request2 and Net_URL2
 */
// // Required for Dropbox.com connector support
// // On composer
// elFinder::$netDrivers['dropbox'] = 'Dropbox';
// // OR on pear
// include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'elFinderVolumeDropbox.class.php';

// // Dropbox driver need next two settings. You can get at https://www.dropbox.com/developers
// define('ELFINDER_DROPBOX_CONSUMERKEY',    '');
// define('ELFINDER_DROPBOX_CONSUMERSECRET', '');
// define('ELFINDER_DROPBOX_META_CACHE_PATH',''); // optional for `options['metaCachePath']`
// ===============================================
// // Required for Google Drive network mount
// // Installation by composer
// // `composer require google/apiclient:^2.0`
// // Enable network mount
// elFinder::$netDrivers['googledrive'] = 'GoogleDrive';
// // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
// define('ELFINDER_GOOGLEDRIVE_CLIENTID',     '');
// define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', '');
// // Required case of without composer
// define('ELFINDER_GOOGLEDRIVE_GOOGLEAPICLIENT', '/path/to/google-api-php-client/vendor/autoload.php');
// ===============================================

// // Required for Google Drive network mount with Flysystem
// // Installation by composer
// // `composer require nao-pon/flysystem-google-drive:~1.1 nao-pon/elfinder-flysystem-driver-ext`
// // Enable network mount
// elFinder::$netDrivers['googledrive'] = 'FlysystemGoogleDriveNetmount';
// // GoogleDrive Netmount driver need next two settings. You can get at https://console.developers.google.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL?cmd=netmount&protocol=googledrive&host=1"
// define('ELFINDER_GOOGLEDRIVE_CLIENTID',     '');
// define('ELFINDER_GOOGLEDRIVE_CLIENTSECRET', '');
// ===============================================
// // Required for One Drive network mount
// //  * cURL PHP extension required
// //  * HTTP server PATH_INFO supports required
// // Enable network mount
// elFinder::$netDrivers['onedrive'] = 'OneDrive';
// // GoogleDrive Netmount driver need next two settings. You can get at https://dev.onedrive.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL/netmount/onedrive/1"
// define('ELFINDER_ONEDRIVE_CLIENTID',     '');
// define('ELFINDER_ONEDRIVE_CLIENTSECRET', '');
// ===============================================

// // Required for Box network mount
// //  * cURL PHP extension required
// // Enable network mount
// elFinder::$netDrivers['box'] = 'Box';
// // Box Netmount driver need next two settings. You can get at https://developer.box.com
// // AND reuire regist redirect url to "YOUR_CONNECTOR_URL"
// define('ELFINDER_BOX_CLIENTID',     '');
// define('ELFINDER_BOX_CLIENTSECRET', '');
// ===============================================

// Required for WZDE access function and any other misc function
require_once 'helper.php';

//Global Variable
$user_ename = getEname();
$user_oid = enameToOid($user_ename);
//true or false for WZDE master access
$masterAccess = getWzdeMasterAccess($user_oid);

function getWzdeMasterAccess($user_oid){
    $access = checkWzdeMasterAccess($user_oid);
    if (strpos($access,'wzde_write') !== false) {
        return true;
    }
    else {
        return false;
    }
}

function getWzdesiteAccess($user_oid,$site) {
    $access = checkWzdeSiteAccess($user_oid,$site);
    //if the user has wzde_write access to site return true
    if (strpos($access,'wzde_write') !== false) {
        return true;
    }
    else {
        return false;
    }
}

function getWzdesiteReadAccess($user_oid,$site) {
    $access = checkWzdeSiteAccess($user_oid,$site);
    //if the user has wzde_write access to site return true
    if (strpos($access,'wzde_write') !== false || strpos($access,'wzde_read') !== false ) {
        return true;
    }
    else {
        return false;
    }
}

/**
 * Simple function to demonstrate how to control file access using "accessControl" callback.
 * This method will disable accessing files/folders starting from '.' (dot)
 *
 * @param  string  $attr  attribute name (read|write|locked|hidden)
 * @param  string  $path  file path relative to volume root directory started with directory separator
 * @return bool|null
 **/
function access($attr, $path, $data, $volume) {
	return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
		? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
		:  null;                                    // else elFinder decide it itself
}

/**
 * Smart logger function
 * Demonstrate how to work with elFinder event api
 *
 * @param  string   $cmd       command name
 * @param  array    $result    command result
 * @param  array    $args      command arguments from client
 * @param  elFinder $elfinder  elFinder instance
 * @return void|true
 **/
function logger($cmd, $result, $args, $elfinder) {
    $log = sprintf('[%s] %s %s:', 
           date('r'), 
           strtoupper(substr( $_SERVER['REMOTE_USER'], strrpos( $_SERVER['REMOTE_USER'], '\\' )+1 )), 
           strtoupper($cmd));
    foreach ($result as $key => $value) {
        if (empty($value)) {
            continue;
        }
        $data = array();
        if (in_array($key, array('error', 'warning'))) {
            array_push($data, implode(' ', $value));
        } else {
            if (is_array($value)) { // changes made to files
                foreach ($value as $file) {
                    $filepath = (isset($file['realpath']) ? $file['realpath'] : $elfinder->realpath($file['hash']));
                    array_push($data, $filepath);
                }
            } else { // other value (ex. header)
                array_push($data, $value);
            }
        }
        $log .= sprintf(' %s(%s)', $key, implode(', ', $data));
    }
    $log .= "\n";

    //$logfile = '../log/log.txt';
    $logfile = '../log/' . date('Y'.'m') . '_log.txt';
    $dir = dirname($logfile);
    if (!is_dir($dir) && !mkdir($dir)) {
        return;
    }
    if (($fp = fopen($logfile, 'a'))) {
        fwrite($fp, $log);
        fclose($fp);
    }
}

function additionalDisabledAction() {
    global $masterAccess;
    
    if ($masterAccess) {
        //return array();
		return array('cut', 'copy', 'paste');
    }

    else {
        //return array('rm','cut','paste');		
        //return array();
		return array('cut', 'copy', 'paste');
    }
}

//return array of site that do not have wzde_write access or empty array for all sites access (master access)
function wzde_siteAccess($zone) {
    global $masterAccess;
    global $user_oid;
    //if user has master access return empty array so it will give access to all sites
    if ($masterAccess) {
        return array();
    }
    //else check each site if true enable write 
    else {
        $zoneFolder=$_SERVER["APPL_PHYSICAL_PATH"] . 'zone/' . $zone . '/';
        $dirs = array_filter(glob($zoneFolder.'*'), 'is_dir');
        $siteArray=array();
        foreach ($dirs as $val) {
            $dir=basename($val);
            //apply site access
            $siteArray[] = array(
                        //'pattern' => '/'.$dir.'/',
						// ATWEB-3895 by WLUO 6/28/18 - wFix WZDE site level access not always respected
						'pattern' => '!^/'.$dir.'!',
                        'read' 		=> getWzdesiteReadAccess($user_oid,$dir),
                        'write' 	=> getWzdesiteAccess($user_oid,$dir),
                        'hidden' 	=> false,
                        'locked' 	=> !(getWzdesiteAccess($user_oid,$dir)),
            );			
            //hide sub .svn .tmb .quarantine Thumbs.db folders
            $siteArray[] = array(
                        'pattern' => '/'.$dir.'.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
                        'hidden' 	=> true
            );             
        }
        return $siteArray;
    }
}


// Documentation for connector options:
// https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options
$opts = array(
	'debug' => true,
	//Bind callbacks for logging
    'bind' => array(
        'mkdir mkfile rename duplicate upload rm paste extract archive' => 'logger'
    ),
	'roots' => array(
/* 		array(
			'driver'        => 'LocalFileSystem',   // driver for accessing file system (REQUIRED)
			'path'          => 'E:/inetpub/wwwroot/wluo/',         // path to files (REQUIRED)
			//'URL'           => '../wluo/', // URL to files (REQUIRED)
			'accessControl' => 'access',             // disable and hide dot starting files (OPTIONAL)
			'alias'         => 'My Home',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
			//'uploadAllow' 	=> array('image'), # allow all image
			'uploadDeny' 	=> array('image/gif', 'text/x-php','text/x-perl'), # Deny gif and perl
			//'uploadOrder'	=> array( 'allow', 'deny' ),
			//'uploadOrder'	=> array( 'deny', 'allow' ),
            'defaults'   	=> array('read' => true, 'write' => true),
            'attributes' 	=> array(
                array( // hide Thumbs.db
                    'pattern'	=> '/Thumbs.db/',
                    'read' 		=> false,
                    'write' 	=> false,
                    'hidden' 	=> true,
                    'locked' 	=> false
                )
			),
			'disabled' 		=> array ('rename')			
		), */
		array(
			'driver'        => 'LocalFileSystem',                               // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/staff/',  // path to files (REQUIRED)
			//'path'          => '..\..\zone\staff',  // path to files (REQUIRED)
			//'accessControl' => 'access',
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),
			'alias'         => 'Staff',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',            
            'attributes' 	=> array_merge(	
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),				
								//merge with site access
								wzde_siteAccess('staff')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            // These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
			"archivers" => array(
				"create"=>array(
					"application/x-tar", 
					"application/x-gzip", 
					"application/x-bzip2", 
					"application/x-xz", 
					"application/zip", 
					"application/x-7z-compressed"
				), 
				"extract"=>array(
					"application/x-tar", 
					"application/x-gzip", 
					"application/x-bzip2", 
					"application/x-xz", 
					"application/zip", 
					"application/x-7z-compressed"
				)
			)			
		),
		array(
			'driver'        => 'LocalFileSystem',                               // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/home/',   // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),			
			'alias'         => 'Home',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',              
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('home')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers' => array(
                'create' => array(
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'a -mx0',
                        'ext'  => '7z'
                    )                    
                ),
                'extract' => array(
                    'application/zip' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -tzip -y',
                        'ext'  => 'zip'
                    ),
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -y',
                        'ext'  => '7z'
                    )
                )
            )            
		),
		array(
			'driver'        => 'LocalFileSystem',                                   // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/customer/',   // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),			
			'alias'         => 'Customer',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',              
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('customer')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            //These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers' => array(
                'create' => array(
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'a -mx0',
                        'ext'  => '7z'
                    )                    
                ),
                'extract' => array(
                    'application/zip' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -tzip -y',
                        'ext'  => 'zip'
                    ),
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -y',
                        'ext'  => '7z'
                    )
                )
            )            
		),
        array(
			'driver'        => 'LocalFileSystem',                                   // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/staffapps/',  // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),
			'alias'         => 'StaffApps',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',            
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('staffapps')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            //These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers' => array(
                'create' => array(
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'a -mx0',
                        'ext'  => '7z'
                    )                    
                ),
                'extract' => array(
                    'application/zip' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -tzip -y',
                        'ext'  => 'zip'
                    ),
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -y',
                        'ext'  => '7z'
                    )
                )
            )
		),
        array(
			'driver'        => 'LocalFileSystem',                                   // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/atsignage/',  // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),
			'alias'         => 'Atsignage',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/^[0-9a-zA-Z\^\&\'\@\{\}\[\]\,\$\=\!\-\#\(\)\.\%\+\~\_ ]+$/u',           
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('atsignage')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            //These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers' => array(
                'create' => array(
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'a -mx0',
                        'ext'  => '7z'
                    )                    
                ),
                'extract' => array(
                    'application/zip' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -tzip -y',
                        'ext'  => 'zip'
                    ),
                    'application/x-7z-compressed' => array(
                        'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                        'argc' => 'x -y',
                        'ext'  => '7z'
                    )
                )
            )
		),
		array(
			'driver'        => 'LocalFileSystem',                               // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/logi/',   // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),
			'alias'         => 'Logi',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',            
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('logi')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            //These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers'     => array(
                    'create' => array(
                        'application/x-7z-compressed' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'a -mx0',
                            'ext'  => '7z'
                        )                    
                    ),
                    'extract' => array(
                        'application/zip' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'x -tzip -y',
                            'ext'  => 'zip'
                        ),
                        'application/x-7z-compressed' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'x -y',
                            'ext'  => '7z'
                        )
                    )
            )
		),
		array(
			'driver'        => 'LocalFileSystem',                               // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/cdn/',   // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),
			'alias'         => 'Cdn',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',            
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('cdn')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            //These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers'     => array(
                    'create' => array(
                        'application/x-7z-compressed' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'a -mx0',
                            'ext'  => '7z'
                        )                    
                    ),
                    'extract' => array(
                        'application/zip' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'x -tzip -y',
                            'ext'  => 'zip'
                        ),
                        'application/x-7z-compressed' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'x -y',
                            'ext'  => '7z'
                        )
                    )
            )
		),
		array(
			'driver'        => 'LocalFileSystem',                               // driver for accessing file system (REQUIRED)
			'path'          => $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/cts/',   // path to files (REQUIRED)
			// ATWEB-2520 - Fix users are able to create folders directly under a zone
			// Set default to be only readable except for master user
			'defaults' 		=> $masterAccess ? array('read' => true, 'write' => true, 'hidden' => false, 'locked' => false) : array('read' => true, 'write' => false, 'hidden' => false, 'locked' => false),
			'alias'         => 'Cts',
            'imgLib'     	=> 'gd',
            'tmbCrop'    	=> false,
            'acceptedName'  => '/(^\w|^\.)[\w\s\.\%\@\-\,\(\)]*$/u',            
            'attributes' 	=> array_merge(			
								array(array( // hide .svn .tmb .quarantine Thumbs.db folders
									'pattern' => '/.(\.svn|\.git|\.tmb|\.quarantine|Thumbs\.db)/',
									'read' 		=> false,
									'write' 	=> true,
									'hidden' 	=> true,
									'locked' 	=> false
								)),
								//merge with site access
								wzde_siteAccess('cts')
			),
            'uploadMaxSize' => 200000000,
			// CWT-8853 - fix issue #3 
			'dispInlineRegex' => '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/html|x-php|plain|application/pdf)$)',
            //These action are disable by default
			'disabled'      => array_merge(array('mkfile','rename'),additionalDisabledAction()),
            'archivers'     => array(
                    'create' => array(
                        'application/x-7z-compressed' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'a -mx0',
                            'ext'  => '7z'
                        )                    
                    ),
                    'extract' => array(
                        'application/zip' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'x -tzip -y',
                            'ext'  => 'zip'
                        ),
                        'application/x-7z-compressed' => array(
                            'cmd'  => $_SERVER["APPL_PHYSICAL_PATH"].'tools\7z.exe',
                            'argc' => 'x -y',
                            'ext'  => '7z'
                        )
                    )
            )
		)		
/*         array(
            'driver' 		=> 'MySQL',
            'host'   		=> 'ATDMariaDBTest.meditech.com',
            'user'  		=> 'web',
            'pass'  		=> 'meditech',
            'db'			=> 'corpapps',
            'path'  		=> 1
        )	 */	
	)
);

//debug call to write out the $opts variable
file_put_contents('opts.txt', print_r($opts, true), LOCK_EX);

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();

