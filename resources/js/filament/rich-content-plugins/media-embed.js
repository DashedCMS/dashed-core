import { Node, mergeAttributes } from '@tiptap/core'

export default Node.create({
    name: 'mediaEmbed',
    group: 'block',
    atom: true,
    draggable: true,
    isolating: true,
    defining: true,

    addAttributes() {
        return {
            src: { default: null },
            mediaId: { default: null },
            alt: { default: '' },
            widthUnit: { default: '%' }, // '%' | 'px'
            width: { default: '100' },   // if '%' → default 100
            height: { default: null },   // only used when widthUnit = 'px'
        }
    },

    parseHTML() {
        return [{ tag: 'div.responsive-image img.media-embed' }]
    },

    renderHTML({ HTMLAttributes }) {
        const {
            src,
            mediaId,
            alt = '',
            widthUnit = '%',
            width = widthUnit === '%' ? '100' : null,
            height = null,
        } = HTMLAttributes

        const wrapperAttrs = {
            class: 'responsive-image',
            'data-image': 'true',
            'data-media-id': mediaId ?? '',
            'data-src': src ?? '',
            'data-alt': alt ?? '',
            'data-width-unit': widthUnit,
            'data-width': width ?? '',
            'data-height': (widthUnit === 'px' ? (HTMLAttributes.height ?? '') : ''),
            style: 'display:block;max-width:100%;width:100%;',
        }

        const imgStyle = [
            'display:block',
            'max-width:100%',
            widthUnit === '%'
                ? `width:${parseFloat(width ?? '100')}%`
                : (width ? `width:${parseInt(width, 10)}px` : ''),
            widthUnit === '%'
                ? 'height:auto'
                : (HTMLAttributes.height ? `height:${parseInt(HTMLAttributes.height, 10)}px` : 'height:auto'),
        ].filter(Boolean).join(';') + ';'

        const imgAttrs = mergeAttributes(
            {
                class: 'media-embed',
                src: src ?? '',
                alt,
                loading: 'lazy',
                decoding: 'async',
                style: imgStyle,
            },
            HTMLAttributes,
            { src: src ?? '' },
        )

        return ['div', wrapperAttrs, ['img', imgAttrs]]
    },

    addCommands() {
        return {
            setMediaEmbed:
                (options = {}) =>
                    ({ chain }) => {
                        const attrs = { ...options }
                        if (attrs.widthUnit === '%') {
                            attrs.height = null
                            if (!attrs.width) attrs.width = '100'
                        }
                        return chain()
                            .focus()
                            .insertContent({ type: this.name, attrs })
                            .run()
                    },

            updateMediaEmbed:
                (options = {}) =>
                    ({ commands }) => {
                        const attrs = { ...options }
                        if (attrs.widthUnit === '%') {
                            attrs.height = null
                            if (!attrs.width) attrs.width = '100'
                        }
                        return commands.updateAttributes(this.name, attrs)
                    },
        }
    },
})
