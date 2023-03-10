# SDW-Keep

Keep requesting [Stable Diffusion WebUI](https://github.com/AUTOMATIC1111/stable-diffusion-webui).


## Keep requesting

0. Install `stable-diffusion-webui` and `node.js`.
1. In *Stable Diffusion WebUI* project, append `--api` to `set COMMANDLINE_ARGS=` in `webui-user.bat`.
2. Run *Stable Diffusion WebUI*.
3. Add one or some `./inputs/*.json` files. See [API guide](https://github.com/AUTOMATIC1111/stable-diffusion-webui/wiki/API).
4. In this project directory, run `node index`.

Generated images are saved to `/outputs/`.


## See output

0. Run a *HTTP Server* which has *PHP* module.
1. Visit `./index.php`.


## Interrupt current batch

Run `node interrupt`.
This does *NOT* halt the server immediately;
*Stable Diffusion WebUI* would finish the current step and return ongoing image.
