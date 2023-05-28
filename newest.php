<?php
$date = scandir('outputs', SCANDIR_SORT_DESCENDING)[0];
$files = array_slice(scandir("outputs/$date"), 2);
$images = array();
foreach($files as $filename) {
    if(!str_ends_with($filename, '.png')) continue;
    $path = "outputs/$date/$filename";
    $images[] = array(
        'date' => $date,
        'name' => $filename,
        'mtime' => stat($path)['mtime']
    );
}
usort($images, function ($a, $b) {
    return $b['mtime'] - $a['mtime'];
});
$images = array_slice($images, 0, 20);

echo json_encode($images);
