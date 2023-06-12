<?php
    $config = json_decode(file_get_contents('./config.json'));
    $url_api = $config->serverOrigin . '/sdapi/v1/';
    $dirpath = './inputs/';

    $models = json_decode(file_get_contents($url_api . 'sd-models'));
    sort($models);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/kong-util@0.7.7/dist/all.js"></script>
    <script> kongUtil.use(); </script>
    <style>
        .range-container {
            display: flex;
        }
        .range-container > span {
            width: 2.5em;
            padding-right: .5em;
        }
    </style>
</head>
<body>
    <header class="container">
        <h1>Batches</h1>
    </header>
    <main class="container">
        <?php foreach($inputs as $name => $input): ?>
            <article class="mb-3 py-3 border-bottom">
                <h2 class="form-check form-switch fs-4">
                    <input type="checkbox" role="switch"
                        id="switch_<?= $name ?>"
                        class="form-check-input" <?= $input->disabled ? '' : 'checked' ?>
                        data-batch-name="<?= $name ?>"
                        data-bs-toggle="collapse" data-bs-target="#collapse_<?= $name ?>"
                        aria-expanded="<?= $input->disabled ? 'false' : 'true' ?>"
                        aria-controls="collapse_<?= $name ?>"
                    >
                    <label for="switch_<?= $name ?>" class="form-check-label"><?= $name ?></label>
                </h2>
                <form id="collapse_<?= $name ?>" class="row ps-4 collapse <?= $input->disabled ? '' : 'show' ?>">
                    <input name="batch_name" type="hidden" value="<?= $name ?>">

                    <dl class="col-6">
                        <dt>model</dt>
                        <dd>
                            <select name="sd_model_checkpoint" class="form-select">
                                <option>default</option>
                                <?php
                                    foreach ($models as $model) {
                                        echo '<option';
                                        if (isset($input->override_settings)
                                            && str_starts_with($model->title, $input->override_settings->sd_model_checkpoint)
                                        ) echo ' selected';
                                        echo '>' . $model->title . '</option>';
                                    }
                                ?>
                            </select>
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>sampler</dt>
                        <dd>
                            <select name="sampler_name" class="form-select">
                                <option>default</option>
                                <?php
                                    foreach($samplers as $sampler) {
                                        echo '<option';
                                        if(isset($input->sampler_name) && $input->sampler_name === $sampler->name) echo ' selected';
                                        echo '>' . $sampler->name . '</option>';
                                    }
                                ?>
                            </select>
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>CFG scale</dt>
                        <dd class="range-container">
                            <span></span>
                            <input name="cfg_scale" value="<?= $input->cfg_scale ?? 7 ?>"
                                type="range" step="0.5" min="1" max="30" class="form-range"
                            >
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>Steps</dt>
                        <dd class="range-container">
                            <span></span>
                            <input name="steps" value="<?= $input->steps ?>"
                                type="range" step="4" min="8" max="128" class="form-range"
                            >
                        </dd>
                    </dl>

                    <dl class="col-md-6">
                        <dt>Prompt</dt>
                        <dd>
                            <textarea name="prompt" class="form-control" rows="6"
                            ><?= htmlspecialchars($input->prompt) ?></textarea>
                        </dd>
                    </dl>

                    <dl class="col-md-6">
                        <dt>Negative Prompt</dt>
                        <dd>
                            <textarea name="negative_prompt" class="form-control" rows="6"
                            ><?= htmlspecialchars($input->negative_prompt) ?></textarea>
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>Width</dt>
                        <dd class="range-container">
                            <span></span>
                            <input name="width" value="<?= $input->width ?>"
                                type="range" step="64" min="256" max="1024" class="form-range"
                            >
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>Height</dt>
                        <dd class="range-container">
                            <span></span>
                            <input name="height" value="<?= $input->height ?>"
                                type="range" step="64" min="256" max="1024" class="form-range"
                            >
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>Batch Size</dt>
                        <dd class="range-container">
                            <span></span>
                            <input name="batch_size" value="<?= $input->batch_size ?>"
                                type="range" min="1" max="4" class="form-range"
                            >
                        </dd>
                    </dl>

                    <dl class="col-6">
                        <dt>Iteration</dt>
                        <dd class="range-container">
                            <span></span>
                            <input name="n_iter" value="<?= $input->n_iter ?>"
                                type="range" min="1" max="16" class="form-range"
                            >
                        </dd>
                    </dl>

                    <div>
                        <button type="button" name="copy" class="btn btn-warning">複製</button>
                        &nbsp;
                        <button type="button" name="save" class="btn btn-info" disabled>儲存</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>
    </main>
    <script>
        $$('.range-container').forEach(con => {
            const span = $('span', con);
            const inputRange = $('[type=range]', con);
            listen(inputRange, 'input', function() {
                span.textContent = this.value;
            });
            span.textContent = inputRange.value;
        });

        $$('form').forEach(form => {
            const saveButton = $('button[name="save"]', form);
            const copyButton = $('button[name="copy"]', form);

            const changeListener = () => {
                saveButton.disabled = false;
                saveButton.textContent = '儲存';
                saveButton.className = 'btn btn-info';
                copyButton.disabled = true;
            }
            $$('input, textarea', form).forEach(input => {
                listen(input, 'input', changeListener);
            });
            $$('select', form).forEach(select => {
                listen(select, 'change', changeListener);
            });

            listen(saveButton, 'click', () => {
                saveButton.disabled = true;
                saveButton.textContent = '儲存中';
                saveButton.className = 'btn btn-warning';
                const formData = new FormData(form);
                fetchJSON(
                    'save_batch.php',
                    {method: 'POST', body: formData}
                ).then(jso => {
                    console.debug(jso);
                    saveButton.textContent = '已於 ' + (new Date).toLocaleTimeString() + ' 儲存';
                    saveButton.className = 'btn btn-light';
                    copyButton.disabled = false;
                    copyButton.textContent = '複製';
                }).catch(err => {
                    console.error(err);
                    saveButton.textContent = '儲存失敗';
                    saveButton.className = 'btn btn-danger';
                });
            });

            listen(copyButton, 'click', () => {
                copyButton.disabled = true;
                fetchStrict(
                    'copy_batch.php?batch_name=' + form.elements.batch_name.value
                ).then(
                    () => location.reload(),
                    () => copyButton.textContent = '複製失敗'
                );
            });
        });

        $$('[type=checkbox]').forEach(checkbox => {
            listen(checkbox, 'change', function() {
                const formData = new FormData();
                formData.set('batch_name', this.dataset.batchName);
                formData.set('disabled', this.checked ? 0 : 1);
                fetchJSON(
                    'save_batch.php',
                    {method: 'POST', body: formData}
                ).then(console.debug)
                .catch(console.error);
            });
        });
    </script>
</body>
</html>