<?php

/* Reminder: always indent with 4 spaces (no tabs). */
// +---------------------------------------------------------------------------+
// | Geeklog 2.1.1                                                             |
// +---------------------------------------------------------------------------+
// | index.php                                                                 |
// |                                                                           |
// | Filemanager browser                                                       |
// +---------------------------------------------------------------------------+
// | Copyright (C) 2014-2015 by the following authors:                         |
// |                                                                           |
// | Authors: Riaan Los       - mail AT riaanlos DOT nl                        |
// |          Simon Georget   - simon AT linea21 DOT com                       |
// |          Kenji ITO       - geeklog AT mystral-kk DOT net                  |
// +---------------------------------------------------------------------------+
// | Original file "index.html" is licensed under MIT License.                 |
// |                                                                           |
// | This program is free software; you can redistribute it and/or             |
// | modify it under the terms of the GNU General Public License               |
// | as published by the Free Software Foundation; either version 2            |
// | of the License, or (at your option) any later version.                    |
// |                                                                           |
// | This program is distributed in the hope that it will be useful,           |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
// | GNU General Public License for more details.                              |
// |                                                                           |
// | You should have received a copy of the GNU General Public License         |
// | along with this program; if not, write to the Free Software Foundation,   |
// | Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.           |
// |                                                                           |
// +---------------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../lib-common.php';

// First of all, check if the current user has access to Filemanager
if (!SEC_inGroup('Root') &&
        ($_CONF['filemanager_disabled'] ||
            (!SEC_inGroup('Filemanager Admin') && !SEC_hasRights('filemanager.admin')))) {
    $display = COM_createHTMLDocument(
        COM_showMessageText($MESSAGE[29], $MESSAGE[30]),
        array('pagetitle' => $MESSAGE[30])
    );

    // Log attempt to access.log
    COM_accessLog("User {$_USER['username']} tried to illegally access the Filemanager.");
    COM_output($display);
    exit;
}

// Default values defined in filemanager.config.js.dist
$_FM_CONF = array(
    '_comment' => 'IMPORTANT : go to the wiki page to know about options configuration https://github.com/simogeo/Filemanager/wiki/Filemanager-configuration-file',
    'options' => array(
        'culture' => 'en',
        'lang' => 'php',
        'theme' => 'flat-dark',             // No configuration as of GL-2.1.1
        'defaultViewMode' => 'grid',
        'autoload' => true,
        'showFullPath' => false,
        'showTitleAttr' => false,           // No configuration as of GL-2.1.1
        'browseOnly' => false,
        'showConfirmation' => true,
        'showThumbs' => true,
        'generateThumbnails' => true,
        'searchBox' => true,
        'listFiles' => true,
        'fileSorting' => 'default',
        'chars_only_latin' => true,
        'dateFormat' => 'd M Y H:i',
        'serverRoot' => true,
        'fileRoot' => false,
        'baseUrl' => false,
        'logger' => false,
        'capabilities' => array('select', 'download', 'rename', 'delete', 'replace'),
        'plugins' => array()
    ),
    'security' => array(
        'allowFolderDownload' => false,     // No configuration as of GL-2.1.1
        'allowChangeExtensions' => false,   // No configuration as of GL-2.1.1
        'allowNoExtension' => false,        // No configuration as of GL-2.1.1
        'uploadPolicy' => 'DISALLOW_ALL',
        'uploadRestrictions' => array(
            'jpg',
            'jpe',                          // No configuration as of GL-2.1.1
            'jpeg',
            'gif',
            'png',
            'svg',
            'txt',
            'pdf',
            'odp',
            'ods',
            'odt',
            'rtf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'csv',                          // No configuration as of GL-2.1.1
            'ogv',
            'mp4',
            'webm',
            'm4v',
            'ogg',
            'mp3',
            'wav',
            'zip',
            'rar'
        )
    ),
    'upload' => array(
        'multiple' => true,                 // No configuration as of GL-2.1.1
        'number' => 5,                      // No configuration as of GL-2.1.1
        'overwrite' => false,
        'imagesOnly' => false,
        'fileSizeLimit' => 16
    ),
    'exclude' => array(
        'unallowed_files' => array(
            '.htaccess',
            'web.config'
        ),
        'unallowed_dirs' => array(
            '_thumbs',
            '.CDN_ACCESS_LOGS',
            'cloudservers'
        ),
        'unallowed_files_REGEXP' => '/^\\./uis',
        'unallowed_dirs_REGEXP' => '/^\\./uis'
    ),
    'images' => array(
        'imagesExt' => array(
            'jpg',
            'jpe',                          // No configuration as of GL-2.1.1
            'jpeg',
            'gif',
            'png',
            'svg'
        ),
        'resize' => array(                  // No configuration as of GL-2.1.1
            'enabled' => true,
            'maxWidth' => 1280,
            'maxHeight' => 1024
        )
    ),
    'videos' => array(
        'showVideoPlayer' => true,
        'videosExt' => array(
            'ogv',
            'mp4',
            'webm',
            'm4v'                           // No configuration as of GL-2.1.1
        ),
        'videosPlayerWidth' => 400,
        'videosPlayerHeight' => 222
    ),
    'audios' => array(
        'showAudioPlayer' => true,
        'audiosExt' => array(
            'ogg',
            'mp3',
            'wav'
        )
    ),
    'pdfs' => array(                        // Disabled as of GL-2.1.1
        'showPdfReader' => true,
        'pdfsExt' => array(
            'pdf',
            'odp'
        ),
        'pdfsReaderWidth' => 640,
        'pdfsReaderHeight' => 480
    ),
    'edit' => array(                        // Disabled as of GL-2.1.1
        'enabled' => true,
        'lineNumbers' => true,
        'lineWrapping' => true,
        'codeHighlight' => false,
        'theme' => 'elegant',
        'editExt' => array(
            'txt',
            'csv'
        )
    ),
    'customScrollbar' => array(             // No configuration as of GL-2.1.1
        'enabled' => true,
        'theme' => 'inset-2-dark',
        'button' => true
    ),
    'extras' => array(
        'extra_js' => array(),
        'extra_js_async' => true
    ),
    'icons' => array(
        'path' => 'images/fileicons/',
        'directory' => '_Open.png',
        'default' => 'default.png'
    ),
    'url' => 'https://github.com/simogeo/Filemanager',
    'version' => '2.0.0-dev'
);

// Values to be overridden by Geeklog (system)
$rootPath = isset($_CONF_FCK['root']) ? $_CONF_FCK['root'] : '/images/';
$rootPath = ltrim($rootPath, '/');
$rootPath = rtrim($rootPath, '/') . '/';

if (isset($_CONF_FCK['imgl'])) {
    $libraryPath = $_CONF_FCK['imgl'];
} elseif (isset($_CONF_FCK['imagelibrary'])) {
    $libraryPath = $_CONF_FCK['imagelibrary'];
} else {
    $libraryPath = '/images/library/';
}

$libraryPath = ltrim($libraryPath, '/');
$libraryPath = rtrim($libraryPath, '/') . '/';

// $relPaths must not start with a '/' and must end with a '/'
$relPaths = array(
    'Image' => $libraryPath . 'Image/',
    'Flash' => $libraryPath . 'Flash/',
    'Media' => $libraryPath . 'Media/',
    'File'  => $libraryPath . 'File/',
    'Root'  => $rootPath,
);

$type = isset($_GET['Type']) ? COM_applyFilter($_GET['Type']) : '';

if (!array_key_exists($type, $relPaths)) {
    $type = 'Image';
}

$fileRoot = $_CONF['path_html'] . $relPaths[$type];
$fileRoot = str_replace('\\', '/', $fileRoot);

if (preg_match('@\Ahttps?://[^/]+(/.*/)filemanager/index\.php@i', COM_getCurrentURL(), $match)) {
    $relPath = $match[1];
} else {
    $relPath = '/';
}

$relPath .= $relPaths[$type];

$_FM_CONF['options']['culture']            = COM_getLangIso639Code();
$_FM_CONF['options']['defaultViewMode']    = $_CONF['filemanager_default_view_mode'];
$_FM_CONF['options']['browseOnly']         = $_CONF['filemanager_browse_only'];
$_FM_CONF['options']['showConfirmation']   = $_CONF['filemanager_show_confirmation'];
$_FM_CONF['options']['showThumbs']         = $_CONF['filemanager_show_thumbs'];
$_FM_CONF['options']['generateThumbnails'] = $_CONF['filemanager_generate_thumbnails'];
$_FM_CONF['options']['searchBox']          = $_CONF['filemanager_search_box'];
$_FM_CONF['options']['fileSorting']        = $_CONF['filemanager_file_sorting'];
$_FM_CONF['options']['chars_only_latin']   = $_CONF['filemanager_chars_only_latin'];
$_FM_CONF['options']['dateFormat']         = $_CONF['filemanager_date_format'];
$_FM_CONF['options']['serverRoot']         = false;
$_FM_CONF['options']['fileRoot']           = $fileRoot;
$_FM_CONF['options']['baseUrl']            = $relPath;
$_FM_CONF['options']['logger']             = $_CONF['filemanager_logger'];

if ($_CONF['filemanager_logger']) {
    $_FM_CONF['options']['logfile'] = $_CONF['path'] . 'logs/error.log';
}

$_FM_CONF['security']['uploadRestrictions'] = $_CONF['filemanager_upload_restrictions'];

$_FM_CONF['upload']['overwrite']     = $_CONF['filemanager_upload_overwrite'];
$_FM_CONF['upload']['imagesOnly']    = $_CONF['filemanager_upload_images_only'];
$_FM_CONF['upload']['fileSizeLimit'] = $_CONF['filemanager_upload_file_size_limit'];

$_FM_CONF['exclude']['unallowed_files']        = $_CONF['filemanager_unallowed_files'];
$_FM_CONF['exclude']['unallowed_dirs']         = $_CONF['filemanager_unallowed_dirs'];
$_FM_CONF['exclude']['unallowed_files_REGEXP'] = $_CONF['filemanager_unallowed_files_regexp'];
$_FM_CONF['exclude']['unallowed_dirs_REGEXP']  = $_CONF['filemanager_unallowed_dirs_regexp'];

$_FM_CONF['images']['imagesExt'] = $_CONF['filemanager_images_ext'];

$_FM_CONF['videos']['showVideoPlayer']    = $_CONF['filemanager_show_video_player'];
$_FM_CONF['videos']['videosExt']          = $_CONF['filemanager_videos_ext'];
$_FM_CONF['videos']['videosPlayerWidth']  = $_CONF['filemanager_videos_player_width'];
$_FM_CONF['videos']['videosPlayerHeight'] = $_CONF['filemanager_videos_player_height'];

$_FM_CONF['audios']['showAudioPlayer'] = $_CONF['filemanager_show_audio_player'];
$_FM_CONF['audios']['audiosExt']       = $_CONF['filemanager_audios_ext'];

// Temporarily disable new features added since Filemanager v1.1.0
$_FM_CONF['pdfs']['showPdfReader'] = false;
$_FM_CONF['edit']['enabled']       = false;

// Writes back into config file
$path = $_CONF['path_html'] . 'filemanager/scripts/filemanager.config.js';
$data = json_encode($_FM_CONF);

if (is_callable('json_last_error') && (json_last_error() !== JSON_ERROR_NONE)) {
    $data = false;
    COM_errorLog('Filemanager: json_encode() failed.  Error code = ' . json_last_error());
}

if ($data !== false) {
    if (@file_put_contents($path, $data) === false) {
        COM_errorLog('Filemanager: configuration file "' . $path . '" is not writable');
    }
}

// Display
header('Expires: on, 01 Jan 1970 00:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title>File Manager</title>
<link rel="stylesheet" type="text/css" href="styles/reset.css" />
<link rel="stylesheet" type="text/css" href="scripts/jquery.filetree/jqueryFileTree.css" />
<link rel="stylesheet" type="text/css" href="scripts/jquery.contextmenu/jquery.contextMenu-1.01.css" />
<link rel="stylesheet" type="text/css" href="scripts/custom-scrollbar-plugin/jquery.mCustomScrollbar.min.css" />
<style type="text/css">
    #loading-wrap {
        position:fixed;
        height:100%;
        width:100%;
        overflow:hidden;
        top:0;
        left:0;
        display:block;
        background: white url(./images/wait30trans.gif) no-repeat center center;
        z-index:999;
    }
</style>

<!-- CSS dynamically added using 'config.options.theme' defined in config file -->
</head>
<body>
<div id="loading-wrap"><!-- loading wrapper / removed when loaded --></div>
<div>
<form id="uploader" method="post">
    <h1></h1>
    <div id="uploadresponse"></div>
    <button id="level-up" name="level-up" type="button" value="LevelUp">&nbsp;</button>
    <button id="home" name="home" type="button" value="Home">&nbsp;</button>
    <input id="mode" name="mode" type="hidden" value="add" /> 
    <input id="currentpath" name="currentpath" type="hidden" />
    <div id="file-input-container">
        <div id="alt-fileinput">
            <input id="filepath" name="filepath" type="text" /><button id="browse" name="browse" type="button" value="Browse"></button>
        </div>
        <input  id="newfile" name="newfile" type="file" />
    </div>
    <button id="upload" name="upload" type="submit" value="Upload" class="em"></button>
    <button id="newfolder" name="newfolder" type="button" value="New Folder" class="em"></button>
    <button id="grid" class="ON" type="button">&nbsp;</button>
    <button id="list" type="button">&nbsp;</button>
</form>
<div id="splitter">
<div id="filetree"></div>
    <div id="fileinfo">
    <h1></h1>
    </div>
</div>
<div id="footer">
<form name="search" id="search" method="get">
    <div>
        <input type="text" value="" name="q" id="q" />
        <a id="reset" href="#" class="q-reset"></a>
        <span class="q-inactive"></span>
    </div>
</form>
<a href="" id="link-to-project"></a>
<div id="folder-info"></div>
</div>

<ul id="itemOptions" class="contextMenu">
    <li class="select"><a href="#select"></a></li>
    <li class="download"><a href="#download"></a></li>
    <li class="rename"><a href="#rename"></a></li>
    <li class="move"><a href="#move"></a></li>
    <li class="replace"><a href="#replace"></a></li>
    <li class="delete separator"><a href="#delete"></a></li>
</ul>

<script type="text/javascript" src="scripts/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="scripts/jquery-browser.js"></script>
<script type="text/javascript" src="scripts/jquery.form-3.24.js"></script>
<script type="text/javascript" src="scripts/jquery.splitter/jquery.splitter-1.5.1.js"></script>
<script type="text/javascript" src="scripts/jquery.filetree/jqueryFileTree.js"></script>
<script type="text/javascript" src="scripts/jquery.contextmenu/jquery.contextMenu-1.01.js"></script>
<script type="text/javascript" src="scripts/jquery.impromptu-3.2.min.js"></script>
<script type="text/javascript" src="scripts/jquery.tablesorter-2.7.2.min.js"></script>
<script type="text/javascript" src="scripts/filemanager.min.js"></script>
</div>
</body>
</html>
