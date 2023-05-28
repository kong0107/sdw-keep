<?php
    $config = json_decode(file_get_contents('./config.json'));
    $url_api = $config->serverOrigin . '/sdapi/v1/';

    $memory = file_get_contents($url_api . 'memory');
    if(false === $memory) {
        http_response_code(500);
        exit;
    }
    $memory = json_decode($memory);

    $models = json_decode(file_get_contents($url_api . 'sd-models'));
    $samplers = json_decode(file_get_contents($url_api . 'samplers'));
    $upscalers = json_decode(file_get_contents($url_api . 'upscalers'));
    $progress = json_decode(file_get_contents($url_api . 'progress'));

    // $apis = array(
    //     'progress',
    //     'options',
    //     'cmd-flags',
    //     'samplers',
    //     'sd-models',
    //     'hypernetworks',
    //     'face-restorers',
    //     'realesrgan-models',
    //     'prompt-styles',
    //     'embeddings'
    // );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- <meta http-equiv="refresh" content="5"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stable Diffusion status</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<body>
    <header class="container">
        <h1>Stable Diffusion status</h1>
        <nav>
            <a class="btn btn-secondary" href="index.php">view outputs</a>
            <button id="refresh-checkpoints" type="button" class="btn btn-info">Refresh Checkpoints</button>
            <?php if($progress->state->job_count): ?>
                <button id="skip" type="button" class="btn btn-danger">skip the current batch</button>
            <?php endif; ?>
        </nav>
    </header>
    <main class="container">
        <section>
            <?php if($progress->state->job_count): ?>
                <div class="row">
                    <dl class="col-sm-6">
                        <dt>progress</dt>
                        <dd>
                            <progress value="<?= $progress->progress ?>"></progress>
                            <?= intval(100 * $progress->progress) ?>%
                        </dd>
                        <dd>
                            <?= $progress->state->sampling_step ?>
                            of
                            <?= $progress->state->sampling_steps ?>
                            steps
                            <?php if($progress->state->job_count > 1): ?>
                                <div class="text-muted">
                                    <?= $progress->state->job_no ?>
                                    of
                                    <?= $progress->state->job_count ?>
                                    jobs
                                </div>
                            <?php endif; ?>
                        </dd>
                    </dl>
                    <dl class="col-sm-6">
                        <dt>memory</dt>
                        <dd>
                            <progress value="<?= ($memory->ram->used / $memory->ram->total) ?>"></progress>
                            <?= intval(100 * $memory->ram->used / $memory->ram->total) ?>%
                            <span class="text-muted">of <?= number_format($memory->ram->total / 1073741824, 1) ?> GB</span>
                        </dd>
                    </dl>
                </div>
                <?php if($progress->current_image): ?>
                    <img src="data:image/png;base64,<?= $progress->current_image ?>" alt="current_image">
                <?php else: ?>
                    <p class="text-muted">image not available yet</p>
                <?php endif; ?>
            <?php else: ?>
                no running job
            <?php endif; ?>
        </section>
        <div class="row">
            <section class="col-lg-6">
                <h2>model list</h2>
                <ul>
                    <?php
                        foreach($models as $model) {
                            $parts = explode('.', $model->title);
                            printf('<li>%s<span class="text-muted">.%s</span></li>', $parts[0], $parts[1]);
                        }
                    ?>
                </ul>
            </section>
            <section class="col-lg-3">
                <h2>sampler list</h2>
                <ul>
                    <?php foreach($samplers as $sampler): ?>
                        <li><?= $sampler->name ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <section class="col-lg-3">
                <h2>upscaler list</h2>
                <ul>
                    <?php foreach($upscalers as $upscaler): ?>
                        <li><?= $upscaler->name ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    </main>
    <footer class="container text-end text-muted">
        <time><?= substr($progress->state->job_timestamp, 8, 6) ?></time>
    </footer>
    <script>
        document.getElementById('skip').addEventListener('click', event => {
            event.target.disabled = true;
            fetch('redirect.php?path=/sdapi/v1/interrupt', {method: 'POST'});
        });
        document.getElementById('refresh-checkpoints').addEventListener('click', event => {
            event.target.disabled = true;
            fetch('redirect.php?path=/sdapi/v1/refresh-checkpoints', {method: 'POST'})
            .then(() => location.reload());
        });
    </script>
</body>
</html>