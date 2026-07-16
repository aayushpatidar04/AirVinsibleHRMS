<!-- resources/js/Components/Common/RichTextEditor.vue -->
<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { Image } from '@tiptap/extension-image'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import { Placeholder } from '@tiptap/extension-placeholder'
import { watch } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Type or paste content here...',
    },
})

const emit = defineEmits(['update:modelValue'])

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Image.configure({
            allowBase64: true,
        }),
        Table.configure({
            resizable: true,
        }),
        TableRow,
        TableHeader,
        TableCell,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

// Sync external changes
watch(() => props.modelValue, (newValue) => {
    if (editor.value && editor.value.getHTML() !== newValue) {
        editor.value.commands.setContent(newValue, false)
    }
})

// Toolbar actions
const toggleBold = () => editor.value?.chain().focus().toggleBold().run()
const toggleItalic = () => editor.value?.chain().focus().toggleItalic().run()
const toggleBulletList = () => editor.value?.chain().focus().toggleBulletList().run()
const toggleOrderedList = () => editor.value?.chain().focus().toggleOrderedList().run()
const insertTable = () => editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()
const addImage = () => {
    const url = window.prompt('Enter image URL')
    if (url) {
        editor.value?.chain().focus().setImage({ src: url }).run()
    }
}
const clearContent = () => editor.value?.chain().focus().clearContent().run()

const isActive = (name, options = {}) => editor.value?.isActive(name, options) ?? false
</script>

<template>
    <div class="border border-gray-300 rounded-lg overflow-hidden bg-white">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-1 px-3 py-2 bg-gray-50 border-b border-gray-200">
            <button type="button" @click="toggleBold" :class="{ 'bg-gray-200': isActive('bold') }"
                class="p-1.5 rounded hover:bg-gray-200 transition text-sm font-bold" title="Bold">B</button>
            <button type="button" @click="toggleItalic" :class="{ 'bg-gray-200': isActive('italic') }"
                class="p-1.5 rounded hover:bg-gray-200 transition text-sm italic" title="Italic">I</button>
            <div class="w-px h-5 bg-gray-300 mx-1"></div>
            <button type="button" @click="toggleBulletList" :class="{ 'bg-gray-200': isActive('bulletList') }"
                class="p-1.5 rounded hover:bg-gray-200 transition text-sm" title="Bullet List">• List</button>
            <button type="button" @click="toggleOrderedList" :class="{ 'bg-gray-200': isActive('orderedList') }"
                class="p-1.5 rounded hover:bg-gray-200 transition text-sm" title="Numbered List">1. List</button>
            <div class="w-px h-5 bg-gray-300 mx-1"></div>
            <button type="button" @click="insertTable" class="p-1.5 rounded hover:bg-gray-200 transition text-sm"
                title="Insert Table">⊞ Table</button>
            <button type="button" @click="addImage" class="p-1.5 rounded hover:bg-gray-200 transition text-sm"
                title="Add Image">🖼️ Image</button>
            <div class="w-px h-5 bg-gray-300 mx-1"></div>
            <button type="button" @click="clearContent"
                class="p-1.5 rounded hover:bg-red-100 text-red-600 transition text-sm" title="Clear">🗑️ Clear</button>
        </div>

        <!-- Editor -->
        <editor-content :editor="editor"
            class="prose prose-sm max-w-none p-4 min-h-[200px] max-h-[400px] overflow-y-auto" />

        <!-- Paste hint -->
        <div class="px-3 py-1.5 bg-gray-50 border-t border-gray-200 text-xs text-gray-400">
            💡 Tip: You can paste images, Excel tables, or formatted text directly
        </div>
    </div>
</template>

<style>
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}

.ProseMirror table {
    border-collapse: collapse;
    width: 100%;
    margin: 0.5em 0;
}

.ProseMirror th,
.ProseMirror td {
    border: 1px solid #d1d5db;
    padding: 6px 10px;
    min-width: 80px;
}

.ProseMirror th {
    background-color: #f3f4f6;
    font-weight: 600;
}

.ProseMirror img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0.5em 0;
}
</style>