(function () {
    'use strict';

    const MAX_SEARCH_LENGTH = 100;
    const validSearchValues = new WeakMap();
    const searchInputSelector = 'input[type="search"]';
    const htmlPattern = /<\s*\/?\s*[a-z!][^>]*>/i;
    const scriptPattern = /\bjavascript\s*:|\bon[a-z]+\s*=/i;
    const sqlPattern = /(?:\b(?:union\s+select|select\s+.+\s+from|insert\s+into|update\s+\w+\s+set|delete\s+from|drop\s+(?:table|database))\b|--|\/\*|\*\/|\b(?:or|and)\b\s+['"]?[\w'"]+\s*=\s*['"]?[\w'"]+)/i;
    let errorId = 0;

    function getDataTable(input) {
        const wrapper = input.closest('.dataTables_wrapper, .dt-container');
        return wrapper ? wrapper.querySelector('table[data-datatable]') : null;
    }

    function getErrorElement(input) {
        const container = input.closest('.dataTables_filter, .dt-search') || input.parentElement;
        let error = container.querySelector('.datatable-search-error');
        if (error) {
            return error;
        }

        error = document.createElement('small');
        error.id = 'datatable-search-error-' + (++errorId);
        error.className = 'datatable-search-error';
        error.setAttribute('role', 'alert');
        error.setAttribute('aria-live', 'polite');
        error.hidden = true;
        error.style.display = 'block';
        error.style.color = '#b02a37';
        container.insertAdjacentElement('afterend', error);

        const describedBy = input.getAttribute('aria-describedby');
        input.setAttribute(
            'aria-describedby',
            describedBy ? describedBy + ' ' + error.id : error.id
        );
        return error;
    }

    function showError(input, message) {
        const error = getErrorElement(input);
        error.textContent = message;
        error.hidden = false;
        input.setAttribute('aria-invalid', 'true');
    }

    function clearError(input) {
        const container = input.closest('.dataTables_filter, .dt-search') || input.parentElement;
        const error = container.querySelector('.datatable-search-error');
        if (error) {
            error.textContent = '';
            error.hidden = true;
        }
        input.removeAttribute('aria-invalid');
    }

    function isInvalidDateQuery(value) {
        const match = value.trim().match(/^(\d{1,2})([/-])(\d{1,2})\2(\d{4})$/);
        if (!match) {
            return false;
        }

        const day = Number(match[1]);
        const month = Number(match[3]);
        const year = Number(match[4]);
        const date = new Date(year, month - 1, day);

        return date.getFullYear() !== year ||
            date.getMonth() !== month - 1 ||
            date.getDate() !== day;
    }

    function handleSearchInput(event) {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.matches(searchInputSelector)) {
            return;
        }

        const table = getDataTable(input);
        if (!table) {
            return;
        }

        input.maxLength = MAX_SEARCH_LENGTH;
        if (!validSearchValues.has(input)) {
            validSearchValues.set(input, input.value);
        }

        if (input.value.length > MAX_SEARCH_LENGTH) {
            input.value = input.value.slice(0, MAX_SEARCH_LENGTH);
            validSearchValues.set(input, input.value);
            showError(input, 'La búsqueda se limitó a ' + MAX_SEARCH_LENGTH + ' caracteres.');
            return;
        }

        const query = input.value;
        if (htmlPattern.test(query) || scriptPattern.test(query) || sqlPattern.test(query)) {
            input.value = validSearchValues.get(input);
            event.preventDefault();
            event.stopImmediatePropagation();
            showError(input, 'La búsqueda contiene caracteres o patrones no permitidos.');
            return;
        }

        if (isInvalidDateQuery(query)) {
            input.value = validSearchValues.get(input);
            event.preventDefault();
            event.stopImmediatePropagation();
            showError(input, 'Ingrese una fecha válida en formato DD/MM/AAAA.');
            return;
        }

        validSearchValues.set(input, query);
        clearError(input);
    }

    function configureSearchInputs() {
        document.querySelectorAll(searchInputSelector).forEach(function (input) {
            if (getDataTable(input)) {
                input.maxLength = MAX_SEARCH_LENGTH;
                if (!validSearchValues.has(input)) {
                    validSearchValues.set(input, input.value);
                }
            }
        });
    }

    document.addEventListener('input', handleSearchInput, true);
    configureSearchInputs();

    if (document.documentElement && window.MutationObserver) {
        const observer = new MutationObserver(configureSearchInputs);
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();
