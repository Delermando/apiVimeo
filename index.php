<?php
ini_set('display_errors', 'off');

// prepare our Consumer Key and Secret
$consumer_key = 'xxxxxxxx';

$consumer_secret = 'xxxxxxxxx';

require_once('library/vimeo.php');

session_start();

$sUploadResult = '';

switch ($_REQUEST['action']) {
    case 'clear': // Clear session
        
        session_destroy();
        session_start();
        break;

    case 'upload': // Upload video
        $vimeo = new phpVimeo($consumer_key, $consumer_secret, $_SESSION['oauth_access_token'], $_SESSION['oauth_access_token_secret']);
        $video_id = $vimeo->upload($_FILES['file']['tmp_name']);
        
        if ($video_id) {
            $sUploadResult = 'Your video has been uploaded and available <a href="http://vimeo.com/'.$video_id.'">here</a> !';
            $vimeo->call('vimeo.videos.setPrivacy', array('privacy' => 'nobody', 'video_id' => $video_id));
            $vimeo->call('vimeo.videos.setTitle', array('title' => $_POST['title'], 'video_id' => $video_id));
            $vimeo->call('vimeo.videos.setDescription', array('description' => $_POST['description'], 'video_id' => $video_id));
        } else {
            $sUploadResult = 'Video Fails to Upload, try again later.';
        }
        break;
    default:
        
        // Create the object and enable caching
        $vimeo = new phpVimeo($consumer_key, $consumer_secret);
        $vimeo->enableCache(phpVimeo::CACHE_FILE, './cache', 300);
        echo "deler";
        break;
}
 
// Setup initial variables
$state = $_SESSION['vimeo_state'];
$request_token = $_SESSION['oauth_request_token'];
$access_token = $_SESSION['oauth_access_token'];

// Coming back
if ($_REQUEST['oauth_token'] != NULL && $_SESSION['vimeo_state'] === 'start') {
    $_SESSION['vimeo_state'] = $state = 'returned';
}
 
// If we have an access token, set it
if ($_SESSION['oauth_access_token'] != null) {
    $vimeo->setToken($_SESSION['oauth_access_token'], $_SESSION['oauth_access_token_secret']);
}
 
$bUploadCase = false;
switch ($_SESSION['vimeo_state']) {
    default:
        // Get a new request token
        $token = $vimeo->getRequestToken();

        // Store it in the session
        $_SESSION['oauth_request_token'] = $token['oauth_token'];
        $_SESSION['oauth_request_token_secret'] = $token['oauth_token_secret'];
        $_SESSION['vimeo_state'] = 'start';

        // Build authorize link

        $authorize_link = $vimeo->getAuthorizeUrl($token['oauth_token'], 'write');
        break;

    case 'returned':

        // Store it
        if ($_SESSION['oauth_access_token'] === NULL && $_SESSION['oauth_access_token_secret'] === NULL) {
            // Exchange for an access token
            $vimeo->setToken($_SESSION['oauth_request_token'], $_SESSION['oauth_request_token_secret']);
            $token = $vimeo->getAccessToken($_REQUEST['oauth_verifier']);
 
            // Store
            $_SESSION['oauth_access_token'] = $token['oauth_token'];
            $_SESSION['oauth_access_token_secret'] = $token['oauth_token_secret'];
            $_SESSION['vimeo_state'] = 'done';

            // Set the token
            $vimeo->setToken($_SESSION['oauth_access_token'], $_SESSION['oauth_access_token_secret']);
        }

        // display upload videofile form

        $bUploadCase = true;
        break;
}

?>
<!DOCTYPE html>
<html lang="en" >
    <head>
        <meta charset="utf-8" />
        <title>Vimeo API - OAuth and Upload Example | Script Tutorials</title>
        <link href="css/main.css" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <header>
            <h2>Vimeo API - OAuth and Upload Example</h2>
            <a href="http://www.script-tutorials.com/vimeo-api-oauth-and-upload-example/" class="stuts">Back to original tutorial on <span>Script Tutorials</span></a>
        </header>
        <img src="vim.png" class="vim" alt="vimeo" />
        <?php if ($_SESSION['vimeo_state'] == 'start'): ?>
        <center>
        <h1>Step 1. OAuth</h1>
        <h2>Click the link to go to Vimeo to authorize your account.</h2>
        <p><a href="<?= $authorize_link ?>"><?php echo $authorize_link ?></a></p>
        </center>
    <?php endif ?>
    <?php if ($bUploadCase && $sUploadResult == ''): ?>
        <center>
        <h1>Step 2. Video info</h1>
        <h2>Now we should send video file, title and description to Vimeo</h2>
        </center>
        <form enctype="multipart/form-data" action="index.php" method="post">
            <input type="hidden" name="action" value="upload" />
            <label for="file">Please choose a file:</label><input name="file" type="file" />
            <label for="title">Title:</label><input name="title" type="text" />
            <label for="description">Description:</label><input name="description" type="text" />
            <input type="submit" value="Upload" />
        </form>
    <?php endif ?>
 
    <?php if ($sUploadResult): ?>
        <center>
        <h1>Step 4. Final</h1>
        <h2><?php echo $sUploadResult ?></h2>
        </center>
    <?php endif ?>
        <br /><center><h2>(<a href="?action=clear">Click here to start over</a>)</h2></center>
    </body>
</html>
