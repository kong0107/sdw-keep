<?php
    $config = json_decode(file_get_contents('./config.json'));
    $url_api = $config->serverOrigin . '/sdapi/v1/';
    $dirpath = './inputs/';

    $models = json_decode(file_get_contents($url_api . 'sd-models'));
    $samplers = json_decode(file_get_contents($url_api . 'samplers'));

    $inputs = array();
    foreach(scandir($dirpath) as $basename) {
        if(!str_ends_with($basename, '.json')) continue;
        $inputs[substr($basename, 0, -5)] = json_decode(
            file_get_contents($dirpath . $basename)
        );
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>input files edit</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/kong-util@0.6.8/dist/all.js"></script>
</head>
<body>
    <header></header>
    <main class="container">
        <?php foreach($inputs as $name => $input): ?>
            <section class="mb-3 py-3 border-bottom">
                <h2 class="form-check form-switch fs-4">
                    <input type="checkbox" role="switch"
                        id="switch_<?= $name ?>" name="<?= $name ?>[enable]"
                        class="form-check-input" <?= $input->disabled ? '' : 'checked' ?>
                        data-bs-toggle="collapse" data-bs-target="#collapse_<?= $name ?>"
                        aria-expanded="<?= $input->disabled ? 'false' : 'true' ?>"
                        aria-controls="collapse_<?= $name ?>"
                    >
                    <label for="switch_<?= $name ?>" class="form-check-label"><?= $name ?></label>
                </h2>
                <dl id="collapse_<?= $name ?>" class="ps-4 collapse <?= $input->disabled ? '' : 'show' ?>">
                    <dt>model</dt>
                    <dd>
                        <select class="form-select">
                            <option>default</option>
                            <?php
                                foreach($models as $model) {
                                    echo '<option';
                                    if($model->title === $input->override_settings->sd_model_checkpoint) echo ' selected';
                                    echo '>' . $model->title . '</option>';
                                }
                            ?>
                        </select>
                    </dd>

                    <dt>sampler</dt>
                    <dd>
                        <select class="form-select">
                            <option>default</option>
                            <?php
                                foreach($samplers as $sampler) {
                                    echo '<option';
                                    if($sampler->name === $input->sampler_name) echo ' selected';
                                    echo '>' . $sampler->name . '</option>';
                                }
                            ?>
                        </select>
                    </dd>

                    <dt>CFG scale</dt>
                    <dd>
                        <input type="number" step="0.5" min="0"
                            value="<?= $input->cfg_scale ?? 7 ?>"
                            class="form-control"
                        >
                    </dd>
                </dl>
            </section>
        <?php endforeach; ?>
    </main>
</body>
</html>