<?php

/**
 * Parse files whose paths are:
 *    `./outputs/<YYYY-MM-DD>/<name>-<HHmmss>.json`
 *    `./outputs/<YYYY-MM-DD>/<name>-<HHmmss>-<seed>.png`
 *
 * # data structure
 *
 * ## read as
    {
        <date>+<name>+<time>: {
            name: <input_filename>,
            date: <YYYY-MM-DD>,
            time: <HHmmss>,
            info: {parameters.sd_model_checkpoint, ...info},
            images: [<seed>, ...]
        },
        ...
    }
 *
 * ## arrange to
    {
        <input_filename>: [{
            ...info,
            images: [{
                date: <YYYY-MM-DD>,
                time: <HHmmss>,
                seed: \d+
            }, ...]
        }, ...],
        ...
    }
 *
 */

// 顯示限制。
$day_limit = intval($_GET['day_limit'] ?? 0);
if($day_limit <= 0) $day_limit = 3;

$data = array();

/**
 * 依檔名順序建出結構。
 */
$day_counts = 0;
foreach(scandir('./outputs', SCANDIR_SORT_DESCENDING) as $dir_name) {
    if(!preg_match('/^(\\d{4})-(\\d{2})-(\\d{2})$/', $dir_name)) continue;

    $dir_path = './outputs/' . $dir_name;
    if(!is_dir($dir_path)) continue;

    foreach(scandir($dir_path) as $basename) {
        if(preg_match('/^(\\w+)-(\\d{6})\\.json$/', $basename, $matches)
            || preg_match('/^(\\w+)-(\\d{6})-(\\d+)\\.png$/', $basename, $matches)
        ) $key = $dir_name . $matches[2] . $matches[1];
        else continue;

        if(!isset($data[$key])) {
            $data[$key] = array(
                'name' => $matches[1],
                'date' => $dir_name,
                'time' => $matches[2],
                'images' => array()
            );
        }
        if(isset($matches[3])) $data[$key]['images'][] = $matches[3];
        else {
            $content = file_get_contents($dir_path . '/' . $basename);
            $content = json_decode($content);
            if(isset($content->detail)) continue;

            $info = $content->info;
            $data[$key]['info'] = array(
                'model' => $content->model ?? $content->parameters?->override_settings?->sd_model_checkpoint,
                'sampler_name' => $info->sampler_name,
                'prompt' => $info->prompt,
                'negative_prompt' => $info->negative_prompt,
                'cfg_scale' => $info->cfg_scale,
                'steps' => $info->steps,
                'width' => $info->width,
                'height' => $info->height
            );
        }
    }
    if(++$day_counts >= $day_limit) break;
}

/**
 * 依時間逆序排列。
 */
ksort($data);
$data = array_reverse($data);


/**
 * 整理成需要的結構。
 */
$struct = array();
foreach($data as $batch) {
    extract($batch);
    if(!isset($struct[$name])) $struct[$name] = array();
    $hash = hash('xxh3', json_encode($info));

    if(!isset($struct[$name][$hash])) {
        $struct[$name][$hash] = $info;
        $struct[$name][$hash]['images'] = array();
    }
    foreach($images as $seed) {
        $struct[$name][$hash]['images'][] = array(
            'date' => $date,
            'time' => $time,
            'seed' => $seed
        );
    }
}
unset($data);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SD image outputs</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        h2 {
            background: linear-gradient(#fff 0%, #ffffffa0 90%, #ffffff00 100%);
        }
        article:last-of-type ~ [type=checkbox] {
            display: none;
        }
        .image-container {
            display: flex;
            overflow-x: auto;
        }
        .image-container img {
            height: 13.5em;
            max-width: 24em;
            object-fit: scale-down;
            cursor: pointer;
        }
        details:not([open]), summary {
            cursor: pointer;
        }
        [type=checkbox]:checked {
            display: none;
        }
        [type=checkbox] {
            width: 15em;
            cursor: pointer;
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
    <header class="p-2">
        Stable Diffusion WebUI (unofficial)
        <h1>keep running service</h1>
        <div class="text-end">
            <a class="btn btn-secondary" href="sd-status.php">check server status</a>
        </div>
        <form class="d-flex mb-2">
            顯示天數：
            <div>
                <input name="day_limit" type="number" min="1" value="<?= $day_limit ?>" class="form-control">
            </div>
            <input type="submit" class="btn btn-success">
        </form>
        <nav>
            <?php foreach(array_keys($struct) as $name): ?>
                <a class="btn btn-primary" href="#<?= $name ?>"><?= $name ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="px-2">
        <?php foreach($struct as $name => $bundle): ?>
            <section class="my-2 p-3 rounded border" id="<?= $name ?>">
                <h2 class="sticky-top"><?= $name ?></h2>
                <?php foreach($bundle as $batch): ?>
                    <article class="border-top mb-2 pt-2">
                        <div class="image-container">
                            <?php foreach($batch['images'] as $image): ?>
                                <figure class="d-flex flex-column m-1">
                                    <img src="outputs/<?= $image['date'] ?>/<?= $name ?>-<?= $image['time'] ?>-<?= $image['seed'] ?>.png" loading="lazy">
                                    <figcaption class="text-center">
                                        <?= $image['date'] ?>
                                        <?= $image['time'] ?>
                                    </figcaption>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="copyMarkdown btn btn-primary">copy MD</button>
                        <button type="button" class="copyJSON btn btn-primary">copy JSON</button>
                        <div class="row">
                            <dl class="col-md-6">
                                <dt>model</dt>
                                <dd title="model"><?= $batch['model'] ?></dd>
                            </dl>
                            <dl class="col-md-6">
                                <dt>sampler</dt>
                                <dd title="sampler_name"><?= $batch['sampler_name'] ?></dd>
                            </dl>

                            <dl class="col-lg-6">
                                <dt>prompt</dt>
                                <dd title="prompt"><?= htmlentities($batch['prompt']) ?></dd>
                            </dl>
                            <dl class="col-lg-6">
                                <dt>negative prompt</dt>
                                <dd title="negative_prompt"><?= $batch['negative_prompt'] ?></dd>
                            </dl>

                            <dl class="col-sm-4">
                                <dt>CFG scale</dt>
                                <dd title="cfg_scale"><?= $batch['cfg_scale'] ?></dd>
                            </dl>
                            <dl class="col-sm-4">
                                <dt>steps</dt>
                                <dd title="steps"><?= $batch['steps'] ?></dd>
                            </dl>
                            <dl class="col-sm-4">
                                <dt>size</dt>
                                <dd title="size">
                                    <span title="width"><?= $batch['width'] ?></span>
                                    &times;
                                    <span title="height"><?= $batch['height'] ?></span>
                                </dd>
                            </dl>
                        </div>
                    </article>
                    <input type="checkbox">
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </main>
    <footer class="p-2 text-end text-white">EOF</footer>
    <div id="lightbox" class="d-none" style="position: fixed; top: 0; left: 0; z-index: 1069; width: 100%; height: 100%; background-color: #00000080; padding: 1em;"><img style="object-fit: contain; width: 100%; height: 100%;"></div>
    <script src="https://cdn.jsdelivr.net/npm/kong-util/dist/all.js"></script>
    <script>
        kongUtil.use();
        const lightbox = $('#lightbox');
        const images = $$('main img');

        images.forEach(img => {
            listen(img, 'click', () => {
                $('img', lightbox).src = img.src;
                lightbox.classList.remove('d-none');
            });
        });

        listen(lightbox, 'click', () => {
            lightbox.classList.add('d-none');
            lightbox.removeAttribute('src');
        });

        listen(document, 'keydown', event => {
            if(event.ctrlKey || event.altKey || event.shiftKey) return;

            let diff;
            switch(event.key) {
                case 'ArrowLeft': diff = -1; break;
                case 'ArrowRight': diff = 1; break;
                default: return;
            }

            const current = $('img', lightbox).src;
            const currentIndex = images.findIndex(img => img.src === current);
            if(currentIndex === -1) console.error('no image');

            const newIndex = currentIndex + diff;
            if(newIndex < 0 || newIndex >= images.length)
                lightbox.dispatchEvent(new Event('click'));
            else $('img', lightbox).src = images[newIndex].src;
        });

        $$('.copyMarkdown').forEach(btn => {
            listen(btn, 'click', () => {
                let result = '';
                const con = btn.closest('article');
                ['prompt', 'negative_prompt'].forEach(param => {
                    result += `# ${param}\n` + $(`[title=${param}]`, con).textContent + '\n\n';
                });
                result += '# config\n';
                ['model', 'sampler_name', 'cfg_scale', 'steps'].forEach(param => {
                    result += `- ${param}: ` + $(`[title=${param}]`, con).textContent + '\n';
                });
                result += '- siez: ' + $(`[title=size]`, con).textContent.replaceAll(/\s+/g, '');
                navigator.clipboard.writeText(result).catch(console.error);
            });
        });
        $$('.copyJSON').forEach(btn => {
            listen(btn, 'click', () => {
                const obj = {};
                const con = btn.closest('article');
                ['prompt', 'negative_prompt', 'sampler_name'].forEach(param => {
                    obj[param] = $(`[title=${param}]`, con).textContent;
                });
                ['cfg_scale', 'steps', 'width', 'height'].forEach(param => {
                    obj[param] = parseFloat($(`[title=${param}]`, con).textContent);
                });
                obj.n_iter = obj.batch_size = 1;
                obj.override_settings = {
                    sd_model_checkpoint: $('[title=model]', con).textContent
                };
                navigator.clipboard.writeText(JSON.stringify(obj, null, '\t')).catch(console.error);
            });
        });
    </script>
</body>
</html>
