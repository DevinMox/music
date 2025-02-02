<?php
if (IS_LOGGED == false) {
    $data = array('status' => 400, 'error' => 'Not logged in');
    echo json_encode($data);
    exit();
}

runPlugin('PreSongThumbnmailUpload', $_FILES);

$thumbnail = 'upload/photos/thumbnail.jpg';
if (!empty($_FILES['thumbnail']['tmp_name'])) {
    $file_info   = array(
        'file' => $_FILES['thumbnail']['tmp_name'],
        'size' => $_FILES['thumbnail']['size'],
        'name' => $_FILES['thumbnail']['name'],
        'type' => $_FILES['thumbnail']['type'],
        'crop' => array(
            'width' => 600,
            'height' => 600
        )
    );
    $music->config->s3_upload = 'off';
    $music->config->ftp_upload = 'off';
    $music->config->spaces = 'off';
    $music->config->backblaze_storage = 'off';
    $music->config->wasabi_storage = 'off';
    $music->config->google_drive = 'off';
    $file_upload = shareFile($file_info);
    if (!empty($file_upload['filename'])) {
        $thumbnail = secure($file_upload['filename'], 0);
        $_SESSION['uploads'][] = $thumbnail;
        $data = array('status' => 200, 'thumbnail' => $thumbnail, 'full_thumbnail' => getMedia($thumbnail));
        runPlugin('AfterSongThumbnmailUpload', $data);
    }
}
?>