<script>
(() => {
    const selector = 'input[data-ascii-mark="true"]';
    const valid = value => value === '' || /^[0-9]+(?:\.[0-9]{1,2})?$/.test(value);
    const editable = value => /^[0-9]*(?:\.[0-9]{0,2})?$/.test(value);
    const proposedValue = (input, inserted) => {
        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;
        return input.value.slice(0, start) + inserted + input.value.slice(end);
    };
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
        if (input.dataset.asciiMarkRejected === 'true' || !editable(proposedValue(input, insertedText))) {
            event.preventDefault();
            input.dataset.asciiMarkRejected = 'true';
            input.setCustomValidity('Use English digits and no more than two decimal places.');
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
        if (!editable(proposedValue(input, pasted)) || !valid(proposedValue(input, pasted))) {
            event.preventDefault();
            input.dataset.asciiMarkRejected = 'true';
            input.setCustomValidity('Use English digits and no more than two decimal places.');
        }
    });
    document.addEventListener('input', event => {
        const input = event.target.closest?.(selector);
        if (!input) return;
        if (input.value === '') delete input.dataset.asciiMarkRejected;
        if (input.dataset.asciiMarkRejected !== 'true') {
            input.setCustomValidity(valid(input.value) ? '' : 'Use English digits and no more than two decimal places.');
            if (valid(input.value)) validateConfiguredMaximum(input);
        }
    });
})();
</script>
