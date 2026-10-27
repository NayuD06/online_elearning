import {
    ClassicEditor,
    Alignment,
    AutoImage,
    BlockQuote,
    Bold,
    Essentials,
    Heading,
    Image,
    ImageCaption,
    ImageInsert,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
    Indent,
    Italic,
    Link,
    List,
    MediaEmbed,
    Paragraph,
    PasteFromOffice,
    SimpleUploadAdapter,
    Table,
    TableToolbar,
    Underline,
} from 'ckeditor5';
import translations from 'ckeditor5/translations/vi.js';
import 'ckeditor5/ckeditor5.css';

/**
 * Turn every <textarea data-editor> into a CKEditor 5 instance.
 * Images dropped into the editor are uploaded to data-upload-url.
 */
export function initEditors() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('textarea[data-editor]').forEach((textarea) => {
        ClassicEditor.create(textarea, {
            licenseKey: 'GPL',
            language: 'vi',
            translations: [translations],
            plugins: [
                Alignment, AutoImage, BlockQuote, Bold, Essentials, Heading, Image, ImageCaption, ImageInsert,
                ImageResize, ImageStyle, ImageToolbar, ImageUpload, Indent, Italic, Link, List, MediaEmbed,
                Paragraph, PasteFromOffice, SimpleUploadAdapter, Table, TableToolbar, Underline,
            ],
            toolbar: [
                'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', 'underline', 'link', '|',
                'bulletedList', 'numberedList', 'outdent', 'indent', 'alignment', '|',
                'insertImage', 'mediaEmbed', 'insertTable', 'blockQuote',
            ],
            image: {
                toolbar: ['imageTextAlternative', 'toggleImageCaption', '|', 'imageStyle:inline', 'imageStyle:block', 'imageStyle:side'],
            },
            table: {
                contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
            },
            mediaEmbed: {
                previewsInData: true,
            },
            simpleUpload: {
                uploadUrl: textarea.dataset.uploadUrl,
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
            },
        }).catch((error) => console.error(error));
    });
}
