<?php
    $date = scandir('outputs', SCANDIR_SORT_DESCENDING)[0];
    $files = array_slice(scandir("outputs/$date"), 2);
    $images = array();
    foreach($files as $filename) {
        if(!str_ends_with($filename, '.png')) continue;
        $path = "outputs/$date/$filename";
        $images[] = array(
            'name' => $filename,
            'mtime' => stat($path)['mtime']
        );
    }
    usort($images, function ($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });
    $images = array_slice($images, 0, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-size: 0;
        }
        body {
            background: #000;
        }
        img {
            object-fit: contain;
            /* width: 25%; */
            height: 50vh;
        }
    </style>
    <meta http-equiv="refresh" content="6">
</head>
<body>
    <?php foreach($images as $image): ?>
        <a target="_blank" href="outputs/<?= $date ?>/<?= $image['name'] ?>"
        ><img alt src="outputs/<?= $date ?>/<?= $image['name'] ?>"></a>
    <?php endforeach; ?>
</body>
</html>
