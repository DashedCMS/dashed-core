import { mergeAttributes, Node } from '@tiptap/core'

/**
 * Zet een willekeurige video-URL om naar een embeddable variant.
 * Ondersteunt YouTube, Vimeo, MP4/WebM, en behoudt originele URL in data-src.
 */
function getEmbedUrl(src, type = 'auto') {
    if (!src) return null

    // al embed? laat staan
    if (src.includes('/embed/')) return src

    const ytMatch = src.match(/[?&]v=([^&]+)/)?.[1]
    const ytShort = src.match(/youtu\.be\/([^?]+)/)?.[1]
    const vimeoMatch = src.match(/vimeo\.com\/(\d+)/)?.[1]
    const fileMatch = src.match(/\.(mp4|webm|ogg)(\?.*)?$/i)

    // autodetect type
    if (type === 'auto' || !type) {
        if (ytMatch || ytShort) type = 'youtube'
        else if (vimeoMatch) type = 'vimeo'
        else if (fileMatch) type = 'mp4'
        else type = 'unknown'
    }

    switch (type) {
        case 'youtube': {
            const id = ytMatch || ytShort
            return id ? `https://www.youtube-nocookie.com/embed/${id}` : src
        }
        case 'vimeo':
            return vimeoMatch ? `https://player.vimeo.com/video/${vimeoMatch}` : src
        case 'mp4':
            return src
        default:
            return src
    }
}

export default Node.create({
    name: 'externalVideo',
    group: 'block',
    atom: true,
    defining: true,
    isolating: true,

    addAttributes() {
        return {
            src: { default: null }, // originele URL uit het formulier
            type: { default: 'auto' },
            ratio: { default: '16:9' },
            maxWidth: { default: '100' },
            widthUnit: { default: '%' },
        }
    },

    parseHTML() {
        return [{ tag: 'div.responsive iframe.external-video' }]
    },

    renderHTML({ HTMLAttributes }) {
        const { type, ratio, maxWidth, widthUnit, src } = HTMLAttributes
        const embedUrl = getEmbedUrl(src, type)

        const wrapperAttrs = {
            class: `responsive ${type}-video`,
            [`data-${type}-video`]: 'true',
            'data-type': type,
            'data-ratio': ratio,
            'data-max-width': maxWidth,
            'data-width-unit': widthUnit,
            'data-src': src,
            style: `max-width:${maxWidth}${widthUnit};width:100%;position:relative;overflow:hidden;`,
        }

        const iframeAttrs = mergeAttributes({
            ...HTMLAttributes,
            class: 'external-video',
            src: embedUrl,
            style: `aspect-ratio:${ratio.replace(':', '/')};width:100%;height:auto;border:0;display:block;min-height:300px;`,
            loading: 'lazy',
        })

        return ['div', wrapperAttrs, ['iframe', iframeAttrs]]
    },

    addCommands() {
        return {
            setExternalVideo:
                (options = {}) =>
                    ({ chain }) => {
                        // Filament stuurt soms [[object]], dus unwrap
                        if (Array.isArray(options) && options.length === 1 && typeof options[0] === 'object') {
                            options = options[0]
                        }

                        const src = options.src?.trim?.()
                        if (!src) return false

                        const embedUrl = getEmbedUrl(src, options.type)

                        return chain()
                            .focus()
                            .insertContent({
                                type: this.name,
                                attrs: {
                                    src,
                                    type: options.type ?? 'auto',
                                    ratio: options.ratio ?? '16:9',
                                    maxWidth: options.maxWidth ?? '100',
                                    widthUnit: options.widthUnit ?? '%',
                                    embedUrl, // handig om te debuggen / eventueel gebruiken
                                },
                            })
                            .run()
                    },

            updateExternalVideo:
                (options = {}) =>
                    ({ commands }) => {
                        if (Array.isArray(options) && options.length === 1 && typeof options[0] === 'object') {
                            options = options[0]
                        }

                        const src = options.src?.trim?.()
                        if (!src) return false

                        const embedUrl = getEmbedUrl(src, options.type)

                        return commands.updateAttributes(this.name, {
                            src,
                            type: options.type ?? 'auto',
                            ratio: options.ratio ?? '16:9',
                            maxWidth: options.maxWidth ?? '100',
                            widthUnit: options.widthUnit ?? '%',
                            embedUrl,
                        })
                    },
        }
    },
})
