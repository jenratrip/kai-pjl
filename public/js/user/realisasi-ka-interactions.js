(function (root) {
    const CHECK_FIELDS = Object.freeze([
        'semboyan21',
        'genta',
        'pintu',
        'semboyan35',
    ]);

    function toggleStatus(ka, status, currentTime) {
        if (!ka || !['JALAN', 'BATAL'].includes(status)) {
            return ka;
        }

        if (ka.status === status) {
            ka.status = 'BELUM';
            ka.aktual = '';

            CHECK_FIELDS.forEach((field) => {
                ka[field] = '';
            });

            return ka;
        }

        ka.status = status;
        ka.aktual = status === 'JALAN' ? currentTime : '';

        CHECK_FIELDS.forEach((field) => {
            ka[field] = status === 'JALAN' ? 'yes' : 'no';
        });

        return ka;
    }

    function setCheckValue(ka, field, value) {
        if (!ka || !CHECK_FIELDS.includes(field) || !['yes', 'no'].includes(value)) {
            return ka;
        }

        ka[field] = value;

        return ka;
    }

    function getSaveIndexes(dirtyIndexes, requestedIndex, requestedIndexes) {
        if (Array.isArray(requestedIndexes)) {
            return [...new Set(requestedIndexes)];
        }

        if (Number.isInteger(requestedIndex) && requestedIndex >= 0) {
            return [requestedIndex];
        }

        return [...new Set(dirtyIndexes)];
    }

    root.RealisasiKaInteractions = Object.freeze({
        getSaveIndexes,
        setCheckValue,
        toggleStatus,
    });
})(window);
