<?php

$url_origin = 'http://127.0.0.1:7860';
$models = file_get_contents($url_origin . '/sdapi/v1/sd-models');
if(false === $models) exit(http_response_code(500));
$models = json_decode($models);

$samplers = file_get_contents($url_origin . '/sdapi/v1/samplers');
if(false === $samplers) exit(http_response_code(500));
$samplers = json_decode($samplers);


$data = array();

/**
 * 依檔名順序建出結構。
 */
foreach(scandir('./outputs') as $basename) {
    if(str_ends_with($basename, '.json')) {
        list($name, $datetime) = explode('_', $basename, 2);
        $datetime = substr($datetime, 0, -5);
        $content = file_get_contents("./outputs/$basename");
        $content = json_decode($content);

        if(!isset($data[$name])) $data[$name] = array();
        $data[$name][$datetime] = $content;
        $data[$name][$datetime]->images = array();
    }
    if(str_ends_with($basename, '.png')) {
        list($name, $date, $time) = explode('_', $basename);
        $data[$name]["{$date}_{$time}"]->images[] = $basename;
    }
}

/**
 * 每個檔名裡，依時間逆序排列。
 */
foreach($data as $name => $batch_arr) {
    uksort($data[$name], function($a, $b) {
        return strcmp($b, $a);
    });
}

/**
 * 各個檔名間，依「最新產出的那份」的時間，逆序排列。
 */
uasort($data, function($a, $b) {
    return strcmp(array_key_first($b), array_key_first($a));
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        .image-container {
            white-space: nowrap;
            overflow-x: auto;
        }
        .image-container img {
            height: 16em;
            max-width: 12em;
            object-fit: scale-down;
        }
        [type=checkbox]:checked {
            display: none;
        }
        [type=checkbox] {
            width: 15em;
        }
        [type=checkbox]::after {
            content: 'Show Previous';
        }
        [type=checkbox]:not(:checked) ~ * {
            display: none;
        }
    </style>
</head>
<body>
    <header class="container">
        Stable Diffusion WebUI (unofficial)
        <h1>keep running service</h1>
        <div class="row">
            <details class="col-lg-8">
                <summary>model list</summary>
                <ul>
                    <?php
                        foreach($models as $model) {
                            $parts = explode('.', $model->title);
                            printf('<li>%s<span class="text-muted">.%s</span></li>', $parts[0], $parts[1]);
                        }
                    ?>
                </ul>
            </details>
            <details class="col-lg-4">
                <summary>sampler list</summary>
                <ul>
                    <?php foreach($samplers as $sampler): ?>
                        <li><?= $sampler->name ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </div>
    </header>
    <main class="container">
        <?php foreach($data as $name => $batch_arr): ?>
            <section class="my-4">
                <h2 class="sticky-top"><?= $name ?></h2>
                <?php foreach($batch_arr as $batch): ?>
                    <article class="border-top mb-2 pt-2">
                        <div class="image-container">
                            <?php foreach($batch->images as $i => $image): ?>
                                <a target="_blank" href="outputs/<?= $image ?>"
                                ><img alt="<?= $batch->info->all_seeds[$i] ?>" src="outputs/<?= $image ?>" loading="lazy"></a>
                            <?php endforeach; ?>
                        </div>
                        <details>
                            <summary>
                                <?php
                                    list($model_name) = explode('.', $batch->parameters->override_settings->sd_model_checkpoint);
                                    echo $model_name;
                                ?>
                                <time class="text-muted">
                                    <?= substr($batch->info->job_timestamp, 2, 6) ?>
                                    <?= substr($batch->info->job_timestamp, 8, 6) ?>
                                </time>
                            </summary>
                            <div class="row">
                                <dl class="col-lg-6">
                                    <dt>prompt</dt>
                                    <dd><?= $batch->parameters->prompt ?></dd>
                                </dl>
                                <dl class="col-lg-6">
                                    <dt>negative prompt</dt>
                                    <dd><?= $batch->parameters->negative_prompt ?></dd>
                                </dl>

                                <dl class="col-6 col-md-3">
                                    <dt>sampler</dt>
                                    <dd><?= $batch->info->sampler_name ?></dd>
                                </dl>
                                <dl class="col-6 col-md-3">
                                    <dt>CFG scale</dt>
                                    <dd><?= $batch->info->cfg_scale ?></dd>
                                </dl>
                                <dl class="col-6 col-md-3">
                                    <dt>steps</dt>
                                    <dd><?= $batch->info->steps ?></dd>
                                </dl>
                                <dl class="col-6 col-md-3">
                                    <dt>size</dt>
                                    <dd>
                                        <?= $batch->parameters->width ?>
                                        &times;
                                        <?= $batch->parameters->height ?>
                                    </dd>
                                </dl>
                            </div>
                        </details>
                    </article>
                    <input type="checkbox">
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </main>
    <footer class="container text-end text-white">EOF</footer>
</body>
</html>