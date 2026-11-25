const csvHeaders = [
    'question_text',
    'question_format',
    'option_a',
    'option_b',
    'option_c',
    'option_d',
    'correct_keys',
    'explanation',
];

function normalizeQuestionFormat(value) {
    const format = value.trim().toLowerCase().replace(/\s+/g, ' ');
    const formats = {
        multiple_choice: 'multiple_choice',
        'multiple choice': 'multiple_choice',
        'pilihan ganda': 'multiple_choice',
        checkbox: 'checkbox',
        true_false: 'true_false',
        'true/false': 'true_false',
        'true / false': 'true_false',
        'benar/salah': 'true_false',
        'benar / salah': 'true_false',
    };

    return formats[format] ?? null;
}

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
    const requiredHeaders = ['question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'explanation'];
    const acceptedHeaders = [...requiredHeaders, 'question_format', 'correct_key', 'correct_keys'];
    const hasAnswerKey = headers.includes('correct_key') || headers.includes('correct_keys');
    if (requiredHeaders.some((header) => !headers.includes(header))
        || !hasAnswerKey
        || headers.some((header) => !acceptedHeaders.includes(header))) {
        throw new Error(`Kolom CSV tidak sesuai. Gunakan header: ${csvHeaders.join(', ')}. Kunci checkbox dipisahkan dengan tanda |.`);
    }

    const headerIndexes = Object.fromEntries(headers.map((header, index) => [header, index]));

    return records.slice(1).map((values, index) => {
        if (values.length !== headers.length) {
            throw new Error(`Jumlah kolom pada baris ${index + 2} tidak sesuai dengan template.`);
        }

        const value = (name) => (values[headerIndexes[name]] ?? '').trim();
        const formatValue = value('question_format');
        const format = formatValue ? normalizeQuestionFormat(formatValue) : 'multiple_choice';
        if (!format) {
            throw new Error(`Format soal pada baris ${index + 2} tidak dikenal. Gunakan Pilihan ganda, Checkbox, atau Benar/Salah.`);
        }

        const correctKeys = value('correct_keys')
            ? value('correct_keys').split('|').map((key) => key.trim().toUpperCase()).filter(Boolean)
            : [value('correct_key').toUpperCase()].filter(Boolean);
        const options = {
            A: value('option_a'),
            B: value('option_b'),
            C: value('option_c'),
        };
        if (value('option_d')) options.D = value('option_d');

        return {
            question_text: value('question_text'),
            question_format: format,
            options,
            correct_keys: correctKeys,
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

function syncQuestionFormat(question) {
    const format = question.querySelector('[data-question-format]')?.value ?? 'multiple_choice';
    const isTrueFalse = format === 'true_false';
    const options = question.querySelectorAll('[data-question-option]');

    options.forEach((input) => {
        const key = input.dataset.questionOption;
        const wrapper = question.querySelector(`[data-question-option-wrap="${key}"]`);
        const isFixedChoice = isTrueFalse && ['A', 'B'].includes(key);

        if (input.dataset.customValue === undefined) {
            input.dataset.customValue = input.value;
        }

        if (isTrueFalse) {
            input.value = isFixedChoice ? (key === 'A' ? 'Benar' : 'Salah') : '';
        } else {
            input.value = input.dataset.customValue;
        }

        input.readOnly = isFixedChoice;
        input.disabled = isTrueFalse && !isFixedChoice;
        input.required = !isTrueFalse && ['A', 'B', 'C'].includes(key);
        wrapper?.classList.toggle('hidden', isTrueFalse && !isFixedChoice);
    });

    const answerInputs = [...question.querySelectorAll('[data-question-correct-choice]')];
    answerInputs.forEach((input) => {
        const key = input.dataset.questionCorrectChoice;
        const option = question.querySelector(`[data-question-option="${key}"]`);
        const wrapper = question.querySelector(`[data-question-correct-wrap="${key}"]`);
        const isAvailable = !isTrueFalse || ['A', 'B'].includes(key);

        input.type = format === 'checkbox' ? 'checkbox' : 'radio';
        input.disabled = !isAvailable || (!isTrueFalse && key === 'D' && !option?.value.trim());
        wrapper?.classList.toggle('hidden', !isAvailable);
    });

    const selected = answerInputs.filter((input) => input.checked && !input.disabled);
    if (format !== 'checkbox' && selected.length > 1) {
        selected.slice(1).forEach((input) => { input.checked = false; });
    }

}

function initializeQuestionEditor(root, questions, validationErrors) {
    const form = root.querySelector('[data-question-bulk-form]');
    const list = form.querySelector('[data-question-list]');
    const template = form.querySelector('[data-question-template]');
    const message = root.querySelector('[data-question-import-message]');
    let nextIndex = 0;

    function appendCard(question = {}, errors = {}, sourceIndex = nextIndex) {
        const index = nextIndex;
        nextIndex += 1;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index));
        const card = wrapper.firstElementChild;
        const options = question.options ?? {};

        card.querySelector('[data-question-number]').textContent = String(list.children.length + 1);
        card.querySelector('[data-question-text]').value = question.question_text ?? '';
        card.querySelector('[data-question-format]').value = question.question_format ?? 'multiple_choice';
        card.querySelectorAll('[data-question-option]').forEach((input) => {
            input.value = options[input.dataset.questionOption] ?? '';
            input.dataset.customValue = input.value;
        });
        const correctKeys = question.correct_keys ?? (question.correct_key ? [question.correct_key] : ['A']);
        card.querySelector('[data-question-explanation]').value = question.explanation ?? '';
        card.querySelectorAll('[data-error-for]').forEach((element) => {
            const sourceKey = element.dataset.errorFor.replace(
                `questions.${index}.`,
                `questions.${sourceIndex}.`,
            );
            const error = errors[element.dataset.errorFor]
                ?? errors[sourceKey]
                ?? errors[`${sourceKey}.0`];
            if (!error) return;
            element.textContent = error;
            element.classList.remove('hidden');
        });
        card.dataset.questionIndex = String(index);
        list.append(card);
        syncQuestionFormat(card);

        // FR-A05, AC-48: set checkbox inputs before restoring multiple imported answer keys.
        card.querySelectorAll('[data-question-correct-choice]').forEach((input) => {
            input.checked = correctKeys.includes(input.value);
        });

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
        const card = event.target.closest('[data-question-card]');
        if (event.target.matches('[data-question-option]') && card) {
            if (card.querySelector('[data-question-format]').value !== 'true_false') {
                event.target.dataset.customValue = event.target.value;
            }
            syncQuestionFormat(card);
        }
    });

    list.addEventListener('change', (event) => {
        const card = event.target.closest('[data-question-card]');
        if (event.target.matches('[data-question-format]') && card) {
            syncQuestionFormat(card);
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
        const field = match[2].replace(/\.correct_keys\.\d+$/, '.correct_keys');
        indexedErrors[index][`questions.${index}.${field}`] = messages[0];
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

// FR-A05, FR-A11: keep option and answer controls aligned with each saved format.
export function initializeQuestionFormatForms() {
    document.querySelectorAll('[data-question-format-form]').forEach((form) => {
        syncQuestionFormat(form);
        form.addEventListener('change', (event) => {
            if (event.target.matches('[data-question-format]')) syncQuestionFormat(form);
        });
        form.addEventListener('input', (event) => {
            if (!event.target.matches('[data-question-option]')) return;
            if (form.querySelector('[data-question-format]').value !== 'true_false') {
                event.target.dataset.customValue = event.target.value;
            }
            syncQuestionFormat(form);
        });
    });
}
