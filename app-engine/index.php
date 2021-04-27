<?php
/**
 * Copyright 2018 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */


namespace Google\Cloud\Samples\AppEngine\Storage;

use Google\Cloud\Storage\StorageClient;
use Google\Auth\Credentials\GCECredentials;

require_once __DIR__ . '/vendor/autoload.php';

header('Access-Control-Allow-Origin: *');

$bucketName = getenv('GOOGLE_STORAGE_BUCKET');
$projectId = getenv('GOOGLE_CLOUD_PROJECT');
$defaultBucketName = sprintf('%s.appspot.com', $projectId);

register_stream_wrapper($projectId);

if ($bucketName == '<your-bucket-name>') {
    return 'Set the GOOGLE_STORAGE_BUCKET environment variable to the name of '
        . 'your cloud storage bucket in <code>app.yaml</code>';
}

if (!in_array('gs', stream_get_wrappers())) {
    return 'This application can only run in AppEngine or the Dev AppServer environment.';
}

/**
 * List Cloud Storage bucket objects.
 *
 * @param string $bucketName the name of your Cloud Storage bucket.
 *
 * @return array
 */
function get_objects($bucket)
{
    $objects = array();
    $storage = new StorageClient();
    $bucket = $storage->bucket($bucket); 
    foreach ($bucket->objects() as $object) {
        $url = 'https://' . $bucket->name() . '.storage.googleapis.com/' . $object->name();
        $objInfo = array();
        $objInfo["name"] = $object->name();
        $objInfo["url"] = $url;
        array_push($objects, $objInfo);
    }

    return $objects;

}

header('Content-type: application/json');

$objArray = get_objects($bucketName);

echo json_encode($objArray,JSON_UNESCAPED_SLASHES);


?>

