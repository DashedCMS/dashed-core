import { mergeAttributes, Node } from '@tiptap/core'

export default Node.create({
    name: 'externalVideo',

    group: 'block',
    atom: true,
    defining: true,
    isolating: true,

    addOptions() {
        return {
            HTMLAttributes: {
                class: 'external-video',
                style:
                    'aspect-ratio:16/9;width:100%;height:auto;border:0;display:block;',
            },
        }
    },

    addAttributes() {
        return {
            src: {
                default: null,
                parseHTML: (element) => element.getAttribute('src'),
            },
            provider: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-provider'),
            },
            ratio: {
                default: '16:9',
                parseHTML: (element) =>
                    element.getAttribute('data-ratio') ?? '16:9',
                renderHTML: (attrs) => ({
                    'data-ratio': attrs.ratio,
                    style: `aspect-ratio:${attrs.ratio.replace(':', '/')};width:100%;height:auto;border:0;display:block;`,
                }),
            },
        }
    },

    parseHTML() {
        return [
            {
                tag: 'iframe.external-video',
            },
        ]
    },

    renderHTML({ HTMLAttributes }) {
        const attrs = mergeAttributes(this.options.HTMLAttributes, HTMLAttributes)
        return ['iframe', attrs]
    },

    addCommands() {
        return {
            setExternalVideo:
                (options = {}) =>
                    ({ chain }) => {
                        const url = options.url?.trim?.()
                        if (!url) return false

                        let provider = 'file'
                        let embedUrl = url

                        const yt = url.match(/[?&]v=([^&]+)/)?.[1]
                        const vm = url.match(/vimeo\.com\/(\d+)/)?.[1]
                        const file = url.match(/\.(mp4|webm|ogg)(\?.*)?$/i)

                        if (yt) {
                            provider = 'youtube'
                            embedUrl = `https://www.youtube.com/embed/${yt}`
                        } else if (vm) {
                            provider = 'vimeo'
                            embedUrl = `https://player.vimeo.com/video/${vm}`
                        } else if (!file) {
                            embedUrl = url.replace('watch?v=', 'embed/')
                            provider = 'youtube'
                        }

                        return chain()
                            .focus()
                            .insertContent({
                                type: this.name,
                                attrs: {
                                    src: embedUrl,
                                    provider,
                                    ratio: options.ratio || '16:9',
                                },
                            })
                            .run()
                    },
        }
    },
})
