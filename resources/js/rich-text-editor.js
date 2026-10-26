import Quill from 'quill';
import 'quill/dist/quill.snow.css';

document.querySelectorAll('[data-rich-text-editor]').forEach((container) => {
    const input = container.querySelector('[data-rich-text-input]');
    const canvas = container.querySelector('[data-rich-text-canvas]');
    if (!input || !canvas || !input.form) return;

    canvas.classList.remove('hidden');
    const quill = new Quill(canvas, {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [false, 1, 2, 3] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ indent: '-1' }, { indent: '+1' }],
                [{ align: [] }],
                ['clean'],
            ],
        },
        formats: ['header', 'bold', 'italic', 'underline', 'strike', 'blockquote', 'list', 'indent', 'align'],
    });

    if (input.value) quill.clipboard.dangerouslyPasteHTML(input.value);
    quill.root.setAttribute('aria-label', container.dataset.label);
    input.classList.add('hidden');

    const syncInput = () => {
        input.value = quill.getText().trim() ? quill.getSemanticHTML() : '';
    };
    quill.on('text-change', syncInput);
    input.form.addEventListener('submit', syncInput);
});
