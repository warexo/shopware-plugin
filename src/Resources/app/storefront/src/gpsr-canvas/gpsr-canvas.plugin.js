const { PluginBaseClass } = window;

export default class GpsrCanvasPlugin extends PluginBaseClass {

    static options = {
        infos: null,
    };

    init() {
        if (!this.options.infos) {
            return;
        }

        const canvas = this.el;
        const style = window.getComputedStyle(canvas);
        const width = Math.max(250, canvas.parentNode.clientWidth);
        const minHeight = Math.max(100, canvas.parentNode.clientHeight);
        const ratio = window.devicePixelRatio || 1;
        const font = this.getFont(style);
        const lineHeight = this.getLineHeight(style);
        const paddingTop = 4;
        const paddingBottom = 4;

        document.fonts.load(font).finally(() => {
            const measureContext = canvas.getContext('2d');
            measureContext.font = font;

            const rawLines = this.getRawLines(this.options.infos);
            const wrappedLines = this.wrapLines(measureContext, rawLines, width);
            const contentHeight = Math.max(
                minHeight,
                Math.ceil((wrappedLines.length * lineHeight) + paddingTop + paddingBottom),
            );

            this.resizeCanvas(canvas, width, contentHeight, ratio);

            const renderContext = canvas.getContext('2d');
            renderContext.font = font;
            renderContext.fillStyle = style.color;
            renderContext.textBaseline = 'top';

            this.drawLines(renderContext, wrappedLines, lineHeight, paddingTop);
        });
    }

    getFont(style) {
        return `${style.fontSize} '${style.fontFamily.split(',').shift()}'`;
    }

    getLineHeight(style) {
        return parseFloat(style.lineHeight) || parseFloat(style.fontSize) * 1.4;
    }

    getRawLines(infos) {
        if (infos.company) {
            return [
                infos.company,
                infos.address,
                [infos.zip, infos.city, infos.country].filter(Boolean).join(' '),
                infos.email,
            ].map((line) => this.normalizeLine(line));
        }

        return this.extractLinesFromHtml(infos.description || '');
    }

    extractLinesFromHtml(html) {
        if (!html) {
            return [];
        }

        const template = document.createElement('template');
        template.innerHTML = html;

        const lines = [];
        let currentLine = '';

        const pushCurrentLine = () => {
            lines.push(this.normalizeLine(currentLine));
            currentLine = '';
        };

        const walk = (node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                currentLine += node.textContent.replace(/\s+/g, ' ');
                return;
            }

            if (node.nodeType !== Node.ELEMENT_NODE && node.nodeType !== Node.DOCUMENT_FRAGMENT_NODE) {
                return;
            }

            if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'BR') {
                pushCurrentLine();
                return;
            }

            const isBlockElement = node.nodeType === Node.ELEMENT_NODE
                && ['DIV', 'P', 'H2', 'H3'].includes(node.tagName);

            if (isBlockElement && this.normalizeLine(currentLine)) {
                pushCurrentLine();
            }

            Array.from(node.childNodes).forEach(walk);

            if (isBlockElement) {
                pushCurrentLine();
            }
        };

        walk(template.content);

        if (this.normalizeLine(currentLine)) {
            pushCurrentLine();
        }

        return lines;
    }

    wrapLines(ctx, lines, maxWidth) {
        return lines.flatMap((line) => {
            if (!line) {
                return [''];
            }

            return this.wrapLine(ctx, line, maxWidth);
        });
    }

    wrapLine(ctx, text, maxWidth) {
        const words = text.split(/\s+/).filter(Boolean);

        if (!words.length) {
            return [''];
        }

        const wrappedLines = [];
        let currentLine = '';

        for (const word of words) {
            const nextLine = currentLine ? `${currentLine} ${word}` : word;

            if (ctx.measureText(nextLine).width <= maxWidth) {
                currentLine = nextLine;
                continue;
            }

            if (currentLine) {
                wrappedLines.push(currentLine);
            }

            if (ctx.measureText(word).width <= maxWidth) {
                currentLine = word;
                continue;
            }

            const wordChunks = this.splitWord(ctx, word, maxWidth);
            wrappedLines.push(...wordChunks.slice(0, -1));
            currentLine = wordChunks[wordChunks.length - 1] || '';
        }

        if (currentLine) {
            wrappedLines.push(currentLine);
        }

        return wrappedLines;
    }

    splitWord(ctx, word, maxWidth) {
        const chunks = [];
        let currentChunk = '';

        for (const character of word) {
            const nextChunk = currentChunk + character;

            if (currentChunk && ctx.measureText(nextChunk).width > maxWidth) {
                chunks.push(currentChunk);
                currentChunk = character;
                continue;
            }

            currentChunk = nextChunk;
        }

        if (currentChunk) {
            chunks.push(currentChunk);
        }

        return chunks;
    }

    drawLines(ctx, lines, lineHeight, paddingTop) {
        let y = paddingTop;

        for (const line of lines) {
            if (line) {
                ctx.fillText(line, 0, y);
            }

            y += lineHeight;
        }
    }

    resizeCanvas(canvas, width, height, ratio) {
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        canvas.getContext('2d').scale(ratio, ratio);
    }

    normalizeLine(line) {
        return (line || '').replace(/\s+/g, ' ').trim();
    }

}