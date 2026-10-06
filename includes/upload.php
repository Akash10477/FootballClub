<?php
/**
 * Safe image upload: checks size + real MIME type, stores with a random file name.
 * Returns the new file name, null if no file was sent, or throws on invalid input.
 */
function handle_image_upload(string $field, string $folder): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try a smaller image.');
    }
    if ($file['size'] > config('uploads.max_bytes')) {
        throw new RuntimeException('Image must be 2 MB or smaller.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = config('uploads.mimes');
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    $dir = ROOT_PATH . '/uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return $name;
}

/** Delete an uploaded file (only if it lives inside the uploads folder). */
function delete_upload(?string $name, string $folder): void
{
    if (!$name) {
        return;
    }
    $path = ROOT_PATH . '/uploads/' . $folder . '/' . basename($name);
    if (is_file($path)) {
        @unlink($path);
    }
}
