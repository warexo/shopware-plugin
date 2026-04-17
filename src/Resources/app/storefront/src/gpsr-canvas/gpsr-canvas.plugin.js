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

        const width = Math.max(250, canvas.parentNode.clientWidth);
        const height = Math.max(100, canvas.parentNode.clientHeight);
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
            if (infos.company) {
                ctx.fillText(infos.company,0,21);
                ctx.fillText(infos.address,0,42);
                ctx.fillText([infos.zip, infos.city, infos.country].join(' '),0,63);
                ctx.fillText(infos.email,0,84);
            }else if(infos.description) {
                ctx.textBaseline = 'top';

                const tmp = document.createElement('div');
                tmp.innerHTML = infos.description;

                const lines = tmp.innerText
                    .split(/\r?\n/)
                    .map((line) => line.replace(/\s+/g, ' ').trim());

                const lineHeight =
                    parseFloat(style.lineHeight) ||
                    parseFloat(style.fontSize) * 1.4;

                let y = 0;

                for (const line of lines) {
                    if (!line) {
                        if (y + lineHeight > height) {
                            break;
                        }

                        y += lineHeight;
                        continue;
                    }

                    for (const wrappedLine of this.wrapLine(ctx, line, width)) {
                        if (y + lineHeight > height) {
                            break;
                        }

                        ctx.fillText(wrappedLine, 0, y);
                        y += lineHeight;
                    }
                }
            }
            
        });


    }

    wrapLine(ctx, text, maxWidth) {
        const result = [];
        const words = text.split(/\s+/);
        let currentLine = '';

        for (const word of words) {
            if (!word) {
                continue;
            }

            if (ctx.measureText(word).width > maxWidth) {
                if (currentLine) {
                    result.push(currentLine);
                    currentLine = '';
                }

                let chunk = '';

                for (const char of word) {
                    const nextChunk = chunk + char;

                    if (chunk && ctx.measureText(nextChunk).width > maxWidth) {
                        result.push(chunk);
                        chunk = char;
                    } else {
                        chunk = nextChunk;
                    }
                }

                currentLine = chunk;
                continue;
            }

            const nextLine = currentLine ? currentLine + ' ' + word : word;

            if (currentLine && ctx.measureText(nextLine).width > maxWidth) {
                result.push(currentLine);
                currentLine = word;
            } else {
                currentLine = nextLine;
            }
        }

        if (currentLine) {
            result.push(currentLine);
        }

        return result;
    }

}