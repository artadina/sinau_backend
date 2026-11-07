const csvHeaders = [
    'question_text',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'correct_key',
    'explanation',
];

export function parseCsv(source) {
    const text = source.replace(/^\uFEFF/, '');
    const records = [];
    let record = [];
    let field = '';
    let quoted = false;

    for (let index = 0; index < text.length; index += 1) {
        const character = text[index];

        if (quoted) {
            if (character === '"' && text[index + 1] === '"') {
                field += '"';
                index += 1;
            } else if (character === '"') {
                quoted = false;
            } else {
                field += character;
            }
            continue;
        }

        if (character === '"' && field === '') {
            quoted = true;
        } else if (character === ',') {
            record.push(field);
            field = '';
        } else if (character === '\n' || character === '\r') {
            record.push(field);
            if (record.some((value) => value.trim() !== '')) records.push(record);
            record = [];
            field = '';
            if (character === '\r' && text[index + 1] === '\n') index += 1;
        } else {
            field += character;
        }
    }

    if (quoted) throw new Error('CSV memiliki tanda kutip yang belum ditutup.');
    record.push(field);
    if (record.some((value) => value.trim() !== '')) records.push(record);

    if (records.length < 2) throw new Error('CSV belum memiliki baris soal untuk diimpor.');

    const headers = records[0].map((value) => value.trim().toLowerCase());
    if (csvHeaders.some((header) => !headers.includes(header)) || headers.length !== csvHeaders.length) {
        throw new Error(`Kolom CSV tidak sesuai. Gunakan header: ${csvHeaders.join(', ')}.`);
    }

    const headerIndexes = Object.fromEntries(headers.map((header, index) => [header, index]));

    return records.slice(1).map((values, index) => {
        if (values.length !== headers.length) {
            throw new Error(`Jumlah kolom pada baris ${index + 2} tidak sesuai dengan template.`);
        }

        const value = (name) => (values[headerIndexes[name]] ?? '').trim();
        const options = {
            A: value('option_a'),
            B: value('option_b'),
            C: value('option_c'),
        };
        if (value('option_d')) options.D = value('option_d');

        return {
            question_text: value('question_text'),
            options,
            correct_key: value('correct_key').toUpperCase(),
            explanation: value('explanation'),
        };
    });
}

function downloadCsvTemplate() {
    const blob = new Blob([`\uFEFF${csvHeaders.join(',')}\r\n`], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'template-bank-soal.csv';
    link.click();
    URL.revokeObjectURL(url);
}

function showMessage(element, text, isError = false) {
    element.textContent = text;
    element.classList.remove('hidden', 'bg-rose-50', 'text-rose-800', 'bg-emerald-50', 'text-emerald-800');
    element.classList.add(isError ? 'bg-rose-50' : 'bg-emerald-50');
    element.classList.add(isError ? 'text-rose-800' : 'text-emerald-800');
}

function initializeQuestionEditor(root, questions, validationErrors) {
    const form = root.querySelector('[data-question-bulk-form]');
    const list = form.querySelector('[data-question-list]');
    const template = form.querySelector('[data-question-template]');
    const message = root.querySelector('[data-question-import-message]');
    let nextIndex = 0;

    function syncCorrectKeys(card) {
        const hasOptionD = card.querySelector('[data-question-option="D"]').value.trim() !== '';
        const optionD = card.querySelector('[data-question-correct] option[value="D"]');
        optionD.disabled = !hasOptionD;
        if (!hasOptionD && card.querySelector('[data-question-correct]').value === 'D') {
            card.querySelector('[data-question-correct]').value = 'A';
        }
    }

    function appendCard(question = {}, errors = {}, sourceIndex = nextIndex) {
        const index = nextIndex;
        nextIndex += 1;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index));
        const card = wrapper.firstElementChild;
        const options = question.options ?? {};

        card.querySelector('[data-question-number]').textContent = String(list.children.length + 1);
        card.querySelector('[data-question-text]').value = question.question_text ?? '';
        card.querySelectorAll('[data-question-option]').forEach((input) => {
            input.value = options[input.dataset.questionOption] ?? '';
        });
        card.querySelector('[data-question-correct]').value = question.correct_key ?? 'A';
        card.querySelector('[data-question-explanation]').value = question.explanation ?? '';
        card.querySelectorAll('[data-error-for]').forEach((element) => {
            const sourceKey = element.dataset.errorFor.replace(
                `questions.${index}.`,
                `questions.${sourceIndex}.`,
            );
            const error = errors[element.dataset.errorFor] ?? errors[sourceKey];
            if (!error) return;
            element.textContent = error;
            element.classList.remove('hidden');
        });
        card.dataset.questionIndex = String(index);
        list.append(card);
        syncCorrectKeys(card);

        return card;
    }

    function syncNumbers() {
        list.querySelectorAll('[data-question-card]').forEach((card, index) => {
            card.querySelector('[data-question-number]').textContent = String(index + 1);
            const previousIndex = card.dataset.questionIndex;
            card.querySelectorAll('[name], [id], [for], [data-error-for]').forEach((element) => {
                ['name', 'id', 'for', 'data-error-for'].forEach((attribute) => {
                    const value = element.getAttribute(attribute);
                    if (!value) return;
                    const updated = value
                        .replace(`questions[${previousIndex}]`, `questions[${index}]`)
                        .replace(`questions.${previousIndex}.`, `questions.${index}.`)
                        .replace(`question-${previousIndex}-`, `question-${index}-`);
                    element.setAttribute(attribute, updated);
                });
            });
            card.dataset.questionIndex = String(index);
        });
        nextIndex = list.children.length;
    }

    root.querySelectorAll('[data-add-question]').forEach((button) => {
        button.addEventListener('click', () => {
            const card = appendCard();
            card.querySelector('[data-question-text]').focus();
        });
    });

    list.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-question]');
        if (!removeButton) return;
        removeButton.closest('[data-question-card]').remove();
        syncNumbers();
    });

    list.addEventListener('input', (event) => {
        if (event.target.matches('[data-question-option="D"]')) {
            syncCorrectKeys(event.target.closest('[data-question-card]'));
        }
    });

    form.addEventListener('submit', (event) => {
        if (list.querySelectorAll('[data-question-card]').length === 0) {
            event.preventDefault();
            showMessage(message, 'Tambahkan minimal satu soal sebelum menyimpan.', true);
        }
    });

    questions.forEach((question, index) => {
        appendCard(question, validationErrors[index] ?? {}, index);
    });

    return { appendCard, list };
}

function validationErrorsByQuestion(errors) {
    const indexedErrors = {};
    Object.entries(errors).forEach(([key, messages]) => {
        const match = key.match(/^questions\.(\d+)\.(.+)$/);
        if (!match) return;
        const index = Number(match[1]);
        indexedErrors[index] ??= {};
        indexedErrors[index][`questions.${index}.${match[2]}`] = messages[0];
    });
    return indexedErrors;
}

function readJson(root, selector) {
    return JSON.parse(root.querySelector(selector)?.textContent || '{}');
}

function asQuestionList(value) {
    if (Array.isArray(value)) return value;
    return Object.keys(value).sort((left, right) => Number(left) - Number(right)).map((key) => value[key]);
}

// FR-A05, AC-42: batch entry opens on demand; CSV review stays on its own page.
export function initializeQuestionBank() {
    document.querySelectorAll('[data-question-bank]').forEach((bank) => {
        const storageKey = bank.dataset.storageKey;
        const modal = bank.querySelector('[data-question-modal]');
        const editor = modal.querySelector('[data-question-editor]');
        const initialQuestions = asQuestionList(readJson(editor, '[data-question-initial-input]'));
        const validationErrors = validationErrorsByQuestion(readJson(editor, '[data-question-validation-errors]'));
        const questionEditor = initializeQuestionEditor(editor, initialQuestions, validationErrors);
        const message = bank.querySelector('[data-question-import-message]');

        if (bank.dataset.clearImport === 'true') {
            try {
                sessionStorage.removeItem(storageKey);
            } catch {
                showMessage(message, 'Impor selesai, tetapi data sementara tidak dapat dibersihkan dari browser.', true);
            }
        }

        bank.querySelector('[data-open-question-modal]').addEventListener('click', () => {
            if (questionEditor.list.children.length === 0) questionEditor.appendCard();
            modal.showModal();
        });

        modal.querySelectorAll('[data-close-question-modal]').forEach((button) => {
            button.addEventListener('click', () => modal.close());
        });

        if (initialQuestions.length || Object.keys(validationErrors).length) {
            modal.showModal();
        }

        bank.querySelector('[data-download-csv-template]').addEventListener('click', downloadCsvTemplate);
        const csvInput = bank.querySelector('[data-question-csv]');
        csvInput.addEventListener('change', async () => {
            const file = csvInput.files?.[0];
            csvInput.value = '';
            if (!file) return;
            if (!file.name.toLowerCase().endsWith('.csv')) {
                showMessage(message, 'Pilih file dengan format CSV.', true);
                return;
            }

            let questions;
            try {
                questions = parseCsv(await file.text());
            } catch (error) {
                showMessage(message, error.message, true);
                return;
            }

            try {
                sessionStorage.setItem(storageKey, JSON.stringify({
                    fileName: file.name,
                    questions,
                }));
                window.location.assign(csvInput.dataset.importUrl);
            } catch {
                showMessage(message, 'Data CSV tidak dapat disimpan sementara di browser. Periksa ruang penyimpanan lalu coba lagi.', true);
            }
        });
    });
}

// FR-A05: render only CSV rows on the dedicated review page, separate from manual cards.
export function initializeQuestionImportPage() {
    document.querySelectorAll('[data-question-import-page]').forEach((page) => {
        const storageKey = page.dataset.storageKey;
        const editor = page.querySelector('[data-question-editor]');
        const initialQuestions = asQuestionList(readJson(editor, '[data-question-initial-input]'));
        const validationErrors = validationErrorsByQuestion(readJson(editor, '[data-question-validation-errors]'));
        let importedBatch = null;
        let storageError = false;

        try {
            importedBatch = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
        } catch {
            storageError = true;
        }

        const questions = initialQuestions.length ? initialQuestions : importedBatch?.questions ?? [];
        const { list } = initializeQuestionEditor(editor, questions, validationErrors);
        const message = page.querySelector('[data-question-import-message]');
        const submitButton = page.querySelector('[data-save-imported-questions]');

        if (importedBatch?.fileName) {
            page.querySelector('[data-import-file-name]').textContent = `${importedBatch.fileName} · ${questions.length} soal`;
        }

        if (questions.length === 0) {
            showMessage(message, 'Data CSV tidak ditemukan. Kembali ke bank soal lalu pilih file CSV lagi.', true);
            submitButton.disabled = true;
            submitButton.classList.add('cursor-not-allowed', 'opacity-50');
        }

        page.querySelectorAll('[data-cancel-question-import]').forEach((link) => {
            link.addEventListener('click', () => {
                try {
                    sessionStorage.removeItem(storageKey);
                } catch {
                    showMessage(message, 'Data CSV sementara tidak dapat dibersihkan dari browser.', true);
                }
            });
        });

        if (storageError) {
            showMessage(message, 'Penyimpanan sementara browser tidak tersedia. Kembali lalu pilih ulang file CSV.', true);
        }

        if (initialQuestions.length && list.children.length) {
            list.querySelector('[data-question-text]')?.focus({ preventScroll: true });
        }
    });
}
