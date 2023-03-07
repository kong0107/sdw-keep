<?php
/**
 * 1. load options
 * 2. arrange queue
 * 3. get some newest results
 */

$url_origin = 'http://127.0.0.1:7860';

/**
 * 1. load options
 */
$models = file_get_contents($url_origin . '/sdapi/v1/sd-models');
if(false === $models) exit(http_response_code(500));
$models = json_decode($models);

$samplers = file_get_contents($url_origin . '/sdapi/v1/samplers');
if(false === $samplers) exit(http_response_code(500));
$samplers = json_decode($samplers);



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <!-- <script src="https://cdn.jsdelivr.net/npm/kong-util/dist/all.js"></script> -->
</head>
<body>
    <header class="container">
        Stable Diffusion WebUI (unofficial)
        <h1>keep running service</h1>
    </header>
    <main class="container">
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button type="button" class="accordion-button collapsed"
                        data-bs-toggle="collapse" data-bs-target="#collapseOne"
                        aria-expanded="false" aria-controls="collapseOne"
                    >&plus; jump the queue</button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show"
                    aria-labelledby="headingOne" data-bs-parent="#accordionExample"
                >
                    <form class="accordion-body">
                        <div class="mb-3">
                            <label class="form-label">Prompt</label>
                            <textarea class="form-control" rows="3">
masterpiece, best quality,</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Negative prompt</label>
                            <textarea class="form-control" rows="3">EasyNegative, worst quality, low quality, low resolution, blurry, monochrome, black and white,
error, text, watermark, username,
female, girl, girl, woman, women, pussy, alien, children, child,
bad anatomy, bad hands, extra fingers, missing fingers, extra digit, fewer digits, cropped, deformed, disfigured, mutation, mutated, extra_limbs,
                            </textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Model</label>
                            <select class="form-select">
                                <?php foreach($models as $model): ?>
                                    <option value="<?= $model->title ?>"><?= $model->model_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sampler</label>
                            <select class="form-select">
                                <?php foreach($samplers as $sampler): ?>
                                    <option><?= $sampler->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Width</label>
                                <input type="number" class="form-control" value="512" min="128" max="1024" step="16">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Height</label>
                                <input type="number" class="form-control" value="640" min="128" max="1024" step="16">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">Batch count</label>
                                <input type="number" class="form-control" value="2" min="1" max="8">
                            </div>
                            <div class="col-6">
                                <label class="form-label">CFG Scale</label>
                                <input type="number" class="form-control" value="7" min="1" max="30" step="0.5">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">jump the queue</button>
                    </form>
                </div>
            </div>
        </div>
        <article class="border rounded my-2 p-2">
            <time>2023-03-07 00:17:34.987</time>
            <div style="white-space: nowrap; overflow-x: auto;">
                <a href="#" data-seed="3421764254"><img src="https://fakeimg.pl/120x180/"></a>
                <a href="#"><img src="https://fakeimg.pl/120x180/"></a>
                <a href="#"><img src="https://fakeimg.pl/120x180/"></a>
                <a href="#"><img src="https://fakeimg.pl/120x180/"></a>
                <a href="#"><img src="https://fakeimg.pl/120x180/"></a>
                <a href="#"><img src="https://fakeimg.pl/120x180/"></a>
                <a href="#"><img src="https://fakeimg.pl/120x180/"></a>
            </div>
            <p title="prompt">an anthro racoon male youth,solo, upper body, nude, big muscle, standing, beautiful eyes, smirking at viewer, in foggy forest, cyberpunk, feral, furry, kemono, masterpiece,best quality,</p>
            <details>
                <dl>
                    <dt>negative prompt</dt>
                    <dd>EasyNegative, worst quality, low quality, monochrome, blurry,low resolution, black and white,text, error,watermark, username, penis, crossing legs, human face, bishonen, shirt, suit, cloth, female,girl,feral,animal,cub,cloth, alien, pussy, girl, woman, women, children, child, breast, bad anatomy, bad hands, extra fingers, missing fingers, extra digit, fewer digits, cropped, blurry, deformed, disfigured, mutation, mutated, extra_limbs</dd>
                    <dt>Model</dt>
                    <dd>anythingfurry_1 [3edd27788a]</dd>
                    <dt>Sampler</dt>
                    <dd>DPM++ 2M Karras</dd>
                    <dt>Size</dt>
                    <dd>512&times;640</dd>
                    <dt>CFG Scale</dt>
                    <dd>7</dd>
                    <dt>Steps</dt>
                    <dd>12</dd>
                </dl>
            </details>
        </article>
        <article class="border rounded my-2 p-2">
            <time>2023-03-07 00:17:34.987</time>
        </article>
        <article class="border rounded my-2 p-2">
            <time>2023-03-07 00:17:34.987</time>
        </article>
    </main>
    <footer class="container"></footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>