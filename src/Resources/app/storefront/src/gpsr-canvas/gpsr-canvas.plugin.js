const { PluginBaseClass } = window;

export default class GpsrCanvasPlugin extends PluginBaseClass {

    static options = {
        infos: null,
    };

    init() {
        if (!this.options.infos) {
            return;
        }

        const infos = this.options.infos;
        const style = window.getComputedStyle(this.el);

        const canvas = this.el;

        const width = canvas.parentNode.clientWidth;
        const height = canvas.parentNode.clientHeight;
        const ratio = window.devicePixelRatio;

        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = width + "px";
        canvas.style.height = height + "px";
        canvas.getContext("2d").scale(ratio, ratio);

        const font = style.fontSize + " '" + style.fontFamily.split(',').shift() + "'";

        document.fonts.load(font).finally(() => {
            const ctx = canvas.getContext("2d");
            ctx.font = font;
            ctx.fillStyle = style.color;
            ctx.fillText(infos.company,0,21);
            ctx.fillText(infos.address,0,42);
            ctx.fillText([infos.zip, infos.city, infos.country].join(' '),0,63);
            ctx.fillText(infos.email,0,84);
        });


    }

}