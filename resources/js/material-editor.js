import { Editor } from '@tiptap/core';
import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';

const maximumImageBytes = 5 * 1024 * 1024;
const maximumDocumentBytes = 10 * 1024 * 1024;
const maximumImages = 10;
const allowedTags = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'H2', 'H3', 'UL', 'OL', 'LI', 'IMG']);

class MaterialImportError extends Error {}

// FR-A16: retain unsaved local images between unit creation and its edit page without small web-storage limits.
function withPendingStore(mode, operation) {
    return new Promise((resolve, reject) => {
        if (!('indexedDB' in window)) {
            reject(new Error('Penyimpanan sementara browser tidak tersedia. Simpan unit dahulu, lalu impor ulang dokumen.'));
            return;
        }

        const request = indexedDB.open('sinau-materials', 1);
        request.onupgradeneeded = () => request.result.createObjectStore('pending');
        request.onerror = () => reject(new Error('Materi sementara belum dapat disimpan di browser.'));
        request.onsuccess = () => {
            const database = request.result;
            const transaction = database.transaction('pending', mode);
            const result = operation(transaction.objectStore('pending'));

            transaction.oncomplete = () => {
                database.close();
                resolve(result?.result);
            };
            transaction.onerror = () => {
                database.close();
                reject(new Error('Materi sementara belum dapat disimpan di browser.'));
            };
            transaction.onabort = () => {
                database.close();
                reject(new Error('Materi sementara belum dapat disimpan di browser.'));
            };
        };
    });
}

function savePendingContent(key, content) {
    return withPendingStore('readwrite', (store) => store.put(content, key));
}

function readPendingContent(key) {
    return withPendingStore('readonly', (store) => store.get(key));
}

function removePendingContent(key) {
    return withPendingStore('readwrite', (store) => store.delete(key));
}

function showMessage(element, message, isError = false) {
    element.textContent = message;
    element.classList.remove('hidden', 'text-rose-700', 'text-emerald-700');
    element.classList.add(isError ? 'text-rose-700' : 'text-emerald-700');
}

// FR-A16, NFR-18: keep only supported editor markup and local/private images.
function sanitizeEditorHtml(html) {
    const documentFragment = new DOMParser().parseFromString(html, 'text/html');

    documentFragment.body.querySelectorAll('*').forEach((element) => {
        if (!allowedTags.has(element.tagName)) {
            element.replaceWith(...element.childNodes);
            return;
        }

        [...element.attributes].forEach((attribute) => {
            const allowed = element.tagName === 'IMG' && ['src', 'alt'].includes(attribute.name);
            if (!allowed) {
                element.removeAttribute(attribute.name);
            }
        });

        if (element.tagName === 'IMG') {
            const source = element.getAttribute('src') ?? '';
            const isLocalImage = source.startsWith('/admin/units/')
                && source.includes('/material/images/')
                && !source.includes('..');
            const isTemporaryImage = /^data:image\/(?:jpeg|png);base64,/i.test(source);

            if (!isLocalImage && !isTemporaryImage) {
                element.remove();
            }
        }
    });

    return documentFragment.body.innerHTML;
}

function countImages(editor) {
    let count = 0;
    editor.state.doc.descendants((node) => {
        if (node.type.name === 'image') {
            count += 1;
        }
    });
    return count;
}

function dataUriToFile(dataUri, filename = 'materi.png') {
    const [header, encoded] = dataUri.split(',');
    const mimeType = header.match(/^data:(.*?);base64$/i)?.[1];

    if (!mimeType || !['image/jpeg', 'image/png'].includes(mimeType)) {
        throw new Error('Gambar harus berformat JPG atau PNG.');
    }

    function dataUriByteLength(dataUri) {
        const encoded = dataUri.split(',')[1] ?? '';
        const padding = encoded.endsWith('==') ? 2 : encoded.endsWith('=') ? 1 : 0;
        return Math.floor((encoded.length * 3) / 4) - padding;
    }

    const binary = atob(encoded);
    const bytes = Uint8Array.from(binary, (character) => character.charCodeAt(0));

    return new File([bytes], filename, { type: mimeType });
}

function readAsDataUri(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('Gambar belum dapat dibaca.'));
        reader.readAsDataURL(file);
    });
}

function escapeHtml(value) {
    const element = document.createElement('span');
    element.textContent = value;
    return element.innerHTML;
}

// FR-A16, AC-46: parse searchable text from a local PDF without uploading the source.
async function extractPdfText(file) {
    let pdfjsLib;
    let pdf;

    try {
        pdfjsLib = await import('pdfjs-dist');
        pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
            'pdfjs-dist/build/pdf.worker.min.mjs',
            import.meta.url,
        ).toString();
        pdf = await pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
    } catch {
        throw new MaterialImportError('PDF belum dapat dibaca. Pastikan berkas tidak rusak.');
    }

    if (pdf.numPages > 50) {
        throw new MaterialImportError('PDF maksimal 50 halaman.');
    }

    const pages = [];

    try {
        for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
            const page = await pdf.getPage(pageNumber);
            const content = await page.getTextContent();
            const text = content.items
                .filter((item) => typeof item.str === 'string')
                .map((item) => item.str.trim())
                .filter(Boolean)
                .join(' ');

            if (text) {
                pages.push(`<p>${escapeHtml(text)}</p>`);
            }

            page.cleanup();
        }
    } catch {
        throw new MaterialImportError('Teks PDF belum dapat dibaca. Pastikan berkas tidak rusak.');
    }

    if (pages.length === 0) {
        throw new MaterialImportError('PDF ini tidak memiliki teks yang dapat diekstrak. PDF hasil scan belum didukung.');
    }

    return pages.join('');
}

// FR-A16, AC-46: preserve supported DOCX formatting and embed images for private upload.
async function extractDocx(file) {
    let result;
    try {
        const mammothModule = await import('mammoth');
        const mammoth = mammothModule.default ?? mammothModule;
        result = await mammoth.convertToHtml(
            { arrayBuffer: await file.arrayBuffer() },
            {
                convertImage: mammoth.images.imgElement(async (image) => ({
                    src: `data:${image.contentType};base64,${await image.read('base64')}`,
                })),
            },
        );
    } catch {
        throw new MaterialImportError('Dokumen DOCX belum dapat dibaca. Pastikan berkas tidak rusak.');
    }

    if (!result.value.trim()) {
        throw new MaterialImportError('Dokumen DOCX tidak berisi materi yang dapat diimpor.');
    }

    return result.value;
}

// FR-A16, AC-45, AC-46: initialize the rich editor and keep imports local until saved.
export function initializeMaterialEditors() {
    document.querySelectorAll('[data-material-editor]').forEach((container) => {
        const editorElement = container.querySelector('[data-material-content]');
        const contentInput = container.querySelector('#reading_content');
        const message = container.querySelector('[data-material-message]');
        const form = container.closest('form');
        const imageUploadUrl = container.dataset.imageUploadUrl;
        const pendingKey = container.dataset.pendingKey;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const imageInput = container.querySelector('[data-material-image-input]');
        const importInput = container.querySelector('[data-material-import-input]');
        const continueEditing = container.querySelector('[data-material-continue]');
        const initialHtml = contentInput.value.trim();
        const initialContent = initialHtml.includes('<')
            ? sanitizeEditorHtml(initialHtml)
            : initialHtml
                ? `<p>${escapeHtml(initialHtml).replace(/\r?\n/g, '<br>')}</p>`
                : '';

        const editor = new Editor({
            element: editorElement,
            extensions: [
                StarterKit.configure({
                    blockquote: false,
                    code: false,
                    codeBlock: false,
                    horizontalRule: false,
                    link: false,
                    heading: { levels: [2, 3] },
                }),
                Image.configure({ inline: false, allowBase64: true }),
            ],
            content: initialContent,
            editorProps: {
                attributes: {
                    class: 'min-h-48 focus:outline-none',
                },
            },
            onUpdate: ({ editor: currentEditor }) => {
                contentInput.value = currentEditor.getHTML();
            },
        });

        async function uploadImage(file) {
            if (!['image/jpeg', 'image/png'].includes(file.type)) {
                throw new Error('Pilih gambar berformat JPG atau PNG.');
            }
            if (file.size > maximumImageBytes) {
                throw new Error('Ukuran gambar maksimal 5 MB.');
            }

            if (!imageUploadUrl) {
                return readAsDataUri(file);
            }

            const body = new FormData();
            body.append('image', file);
            const response = await fetch(imageUploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
                body,
            });
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message ?? result.errors?.image?.[0] ?? 'Gambar belum dapat disimpan.');
            }

            return result.url;
        }

        function addImage(source, alt = '') {
            editor.chain().focus().setImage({ src: source, alt }).run();
        }

        async function persistInlineImages() {
            if (!imageUploadUrl) {
                return;
            }

            const pendingImages = [];
            editor.state.doc.descendants((node, position) => {
                if (node.type.name === 'image' && node.attrs.src?.startsWith('data:')) {
                    pendingImages.push({ node, position });
                }
            });

            for (const { node, position } of pendingImages) {
                const file = dataUriToFile(node.attrs.src);
                const url = await uploadImage(file);
                const transaction = editor.state.tr.setNodeMarkup(position, undefined, {
                    ...node.attrs,
                    src: url,
                });
                editor.view.dispatch(transaction);
            }
        }

        container.querySelectorAll('[data-material-command]').forEach((button) => {
            button.addEventListener('click', () => {
                const command = button.dataset.materialCommand;
                const chain = editor.chain().focus();

                if (command === 'paragraph') chain.setParagraph().run();
                if (command === 'heading') chain.toggleHeading({ level: Number(button.dataset.level) }).run();
                if (command === 'bold') chain.toggleBold().run();
                if (command === 'italic') chain.toggleItalic().run();
                if (command === 'bulletList') chain.toggleBulletList().run();
                if (command === 'orderedList') chain.toggleOrderedList().run();
            });
        });

        container.querySelector('[data-material-add-image]').addEventListener('click', () => imageInput.click());
        imageInput.addEventListener('change', async () => {
            const file = imageInput.files?.[0];
            imageInput.value = '';

            if (!file) return;
            if (countImages(editor) >= maximumImages) {
                showMessage(message, 'Materi maksimal berisi 10 gambar.', true);
                return;
            }

            try {
                addImage(await uploadImage(file), file.name.replace(/\.[^.]+$/, ''));
                message.classList.add('hidden');
            } catch (error) {
                showMessage(message, error.message, true);
            }
        });

        container.querySelector('[data-material-import-trigger]').addEventListener('click', () => importInput.click());
        importInput.addEventListener('change', async () => {
            const file = importInput.files?.[0];
            importInput.value = '';
            if (!file) return;

            const extension = file.name.split('.').pop()?.toLowerCase();
            if (extension === 'doc') {
                showMessage(message, 'Dokumen DOC lama belum didukung. Pilih DOCX atau PDF berteks.', true);
                return;
            }
            if (!['docx', 'pdf'].includes(extension)) {
                showMessage(message, 'Pilih dokumen DOCX atau PDF.', true);
                return;
            }
            if (file.size > maximumDocumentBytes) {
                showMessage(message, 'Ukuran dokumen maksimal 10 MB.', true);
                return;
            }

            try {
                let importedHtml = extension === 'docx'
                    ? await extractDocx(file)
                    : await extractPdfText(file);
                const importedDocument = new DOMParser().parseFromString(importedHtml, 'text/html');
                let addedCount = 0;
                const warnings = [];

                for (const image of [...importedDocument.body.querySelectorAll('img')]) {
                    const source = image.getAttribute('src') ?? '';
                    let importedFile;

                    try {
                        if (dataUriByteLength(source) > maximumImageBytes) {
                            throw new Error('Gambar lebih dari 5 MB tidak ditambahkan.');
                        }
                        importedFile = dataUriToFile(source);
                        if (addedCount >= maximumImages) {
                            throw new Error('Batas 10 gambar per materi tercapai.');
                        }
                        const uploadedUrl = await uploadImage(importedFile);
                        image.setAttribute('src', uploadedUrl);
                        addedCount += 1;
                    } catch (error) {
                        image.remove();
                        warnings.push(error.message);
                    }
                }

                importedHtml = sanitizeEditorHtml(importedDocument.body.innerHTML);
                if (!importedHtml.trim()) {
                    throw new Error('Dokumen tidak berisi materi yang dapat diimpor.');
                }

                editor.commands.setContent(importedHtml);
                showMessage(
                    message,
                    warnings.length ? `Materi diimpor. ${[...new Set(warnings)].join(' ')}` : 'Materi berhasil diimpor dan siap diedit.',
                    warnings.length > 0,
                );
            } catch (error) {
                const errorMessage = error instanceof MaterialImportError
                    ? error.message
                    : 'Dokumen belum dapat diproses. Materi yang tersimpan tidak berubah.';
                showMessage(message, errorMessage, true);
            }
        });

        let pendingContentRestored = false;
        readPendingContent(pendingKey)
            .then((pendingContent) => {
                if (!pendingContent) return;
                pendingContentRestored = true;
                editor.commands.setContent(sanitizeEditorHtml(pendingContent));
                if (imageUploadUrl) {
                    return persistInlineImages().catch((error) => {
                        showMessage(message, `${error.message} Coba simpan unit setelah gambar berhasil diunggah.`, true);
                    });
                }
            })
            .catch((error) => showMessage(message, error.message, true));

        form.addEventListener('submit', (event) => {
            contentInput.value = editor.getHTML();
            const containsInlineImages = contentInput.value.includes('src="data:image/');

            if (imageUploadUrl && containsInlineImages) {
                event.preventDefault();
                persistInlineImages()
                    .then(() => form.requestSubmit(event.submitter))
                    .catch((error) => showMessage(message, error.message, true));
                return;
            }

            if (!imageUploadUrl && containsInlineImages) {
                event.preventDefault();
                savePendingContent(pendingKey, contentInput.value)
                    .then(() => {
                        contentInput.value = contentInput.value.replace(/<img\b[^>]*\bsrc="data:image\/[^"]*"[^>]*>/gi, '');
                        continueEditing.value = '1';
                        form.submit();
                    })
                    .catch((error) => showMessage(message, error.message, true));
                return;
            }

            if (pendingContentRestored) {
                event.preventDefault();
                removePendingContent(pendingKey)
                    .then(() => {
                        pendingContentRestored = false;
                        form.requestSubmit(event.submitter);
                    })
                    .catch((error) => showMessage(message, error.message, true));
            }
        });

        editor.commands.setContent(initialContent);
    });
}
