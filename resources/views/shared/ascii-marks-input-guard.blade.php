<script>
(() => {
    const selector = 'input[data-ascii-mark="true"]';
    const valid = value => value === '' || /^[0-9]+$/.test(value);
    const validateConfiguredMaximum = input => {
        const configuredMaximum = input.dataset.configuredMaximum;
        if (configuredMaximum === undefined || input.value === '' || !valid(input.value)) return true;
        if (Number(input.value) <= Number(configuredMaximum)) return true;
        input.setCustomValidity(`Marks must not exceed the configured maximum of ${configuredMaximum}.`);
        return false;
    };

    document.addEventListener('beforeinput', event => {
        const input = event.target.closest?.(selector);
        if (!input || event.isComposing) return;
        if (event.inputType.startsWith('delete')) {
            delete input.dataset.asciiMarkRejected;
            input.setCustomValidity('');
            return;
        }
        if (!event.inputType.startsWith('insert')) return;
        if (event.data === '') {
            delete input.dataset.asciiMarkRejected;
            input.setCustomValidity('');
            return;
        }
        const insertedText = typeof event.data === 'string' ? event.data : null;
        if (insertedText === null) {
            delete input.dataset.asciiMarkRejected;
            input.setCustomValidity('');
            return;
        }
        if (input.dataset.asciiMarkRejected === 'true' || (insertedText !== null && insertedText !== '' && !/^[0-9]+$/.test(insertedText))) {
            event.preventDefault();
            input.dataset.asciiMarkRejected = 'true';
            input.setCustomValidity('Use English digits (0-9) only. Clear this field and enter the mark again.');
        }
    });
    document.addEventListener('paste', event => {
        const input = event.target.closest?.(selector);
        if (!input) return;
        const pasted = event.clipboardData?.getData('text') ?? '';
        if (pasted === '' && input.value === '') {
            delete input.dataset.asciiMarkRejected;
            input.setCustomValidity('');
            return;
        }
        if (!/^[0-9]+$/.test(pasted)) {
            event.preventDefault();
            input.dataset.asciiMarkRejected = 'true';
            input.setCustomValidity('Use English digits (0-9) only. Clear this field and enter the mark again.');
        }
    });
    document.addEventListener('input', event => {
        const input = event.target.closest?.(selector);
        if (!input) return;
        if (input.value === '') delete input.dataset.asciiMarkRejected;
        if (input.dataset.asciiMarkRejected !== 'true') {
            input.setCustomValidity(valid(input.value) ? '' : 'Use English digits (0-9) only.');
            if (valid(input.value)) validateConfiguredMaximum(input);
        }
    });
})();
</script>
