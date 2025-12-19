import { Extension } from '@tiptap/core'

export default Extension.create({
    name: 'htmlId',

    addGlobalAttributes() {
        return [
            {
                types: ['paragraph', 'heading', 'blockquote', 'codeBlock', 'listItem', 'tableCell', 'tableHeader'],
                attributes: {
                    id: {
                        default: null,
                        parseHTML: element => element.getAttribute('id') || null,
                        renderHTML: attributes => console.log(attributes) && (attributes.id ? { id: attributes.id } : {}),
                    },
                },
            },
        ]
    },

    addCommands() {
        const allowedTypes = new Set(['paragraph', 'heading', 'blockquote', 'codeBlock', 'listItem', 'tableCell', 'tableHeader'])

        const getTargetNodeTypeName = (state) => {
            const { selection } = state

            // 1) als je echt een node selecteert (klik op block / node selection)
            if (selection?.node && allowedTypes.has(selection.node.type.name)) {
                return selection.node.type.name
            }

            // 2) cursor in tekst: pak parent block
            const parent = selection?.$from?.parent
            if (parent && allowedTypes.has(parent.type.name)) {
                return parent.type.name
            }

            // 3) fallback: zoek omhoog tot we een allowed type vinden
            const $from = selection?.$from
            if ($from) {
                for (let d = $from.depth; d > 0; d--) {
                    const node = $from.node(d)
                    if (node && allowedTypes.has(node.type.name)) {
                        return node.type.name
                    }
                }
            }

            return null
        }

        return {
            setHtmlId:
                (options = {}) =>
                    ({ state, commands }) => {
                        // Filament sometimes wraps args
                        if (Array.isArray(options) && options.length === 1 && typeof options[0] === 'object') {
                            options = options[0]
                        }

                        // support both: setHtmlId("abc") and setHtmlId({ id: "abc" })
                        const id = (typeof options === 'string' ? options : options?.id)?.trim?.()
                        if (!id) return false

                        const typeName = getTargetNodeTypeName(state)
                        if (!typeName) return false

                        return commands.updateAttributes(typeName, { id })
                    },

            clearHtmlId:
                () =>
                    ({ state, commands }) => {
                        const typeName = getTargetNodeTypeName(state)
                        if (!typeName) return false

                        return commands.updateAttributes(typeName, { id: null })
                    },
        }
    },
})
