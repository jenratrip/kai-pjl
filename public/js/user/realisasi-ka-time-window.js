(function (root) {
    const MINUTES_PER_DAY = 1440;
    const WINDOW_MINUTES = 60;

    function timeToMinutes(value) {
        if (!value || value === '-') {
            return null;
        }

        const match = String(value).trim().match(
            /^(\d{1,2}):(\d{2})(?::(\d{2}))?$/
        );

        if (!match) {
            return null;
        }

        const hours = Number(match[1]);
        const minutes = Number(match[2]);
        const seconds = Number(match[3] ?? 0);

        if (
            !Number.isInteger(hours)
            || !Number.isInteger(minutes)
            || !Number.isInteger(seconds)
            || hours < 0
            || hours > 23
            || minutes < 0
            || minutes > 59
            || seconds < 0
            || seconds > 59
        ) {
            return null;
        }

        return hours * 60 + minutes + seconds / 60;
    }

    function getWindow(nowMinutes) {
        return {
            startMinutes: nowMinutes,
            endMinutes: nowMinutes + WINDOW_MINUTES,
        };
    }

    function isWithinNextHour(scheduleTime, nowMinutes) {
        const scheduledMinutes = timeToMinutes(scheduleTime);

        if (scheduledMinutes === null) {
            return false;
        }

        const currentMinutes = ((nowMinutes % MINUTES_PER_DAY) + MINUTES_PER_DAY) % MINUTES_PER_DAY;
        const difference = ((scheduledMinutes - currentMinutes) % MINUTES_PER_DAY + MINUTES_PER_DAY) % MINUTES_PER_DAY;

        return difference <= WINDOW_MINUTES;
    }

    function hasRecordedRealization(ka) {
        return ['JALAN', 'BATAL'].includes(ka.status)
            || Boolean(ka.aktual)
            || ['semboyan21', 'genta', 'pintu', 'semboyan35'].some((field) => (
                ka[field] === 'yes' || ka[field] === 'no'
            ))
            || String(ka.keterangan ?? '').trim() !== '';
    }

    function minutesFromShiftStart(value, shiftStart, shiftEnd) {
        const valueMinutes = timeToMinutes(value);
        const startMinutes = timeToMinutes(shiftStart);
        const endMinutes = timeToMinutes(shiftEnd);

        if (valueMinutes === null || startMinutes === null || endMinutes === null) {
            return null;
        }

        const shiftDuration = (endMinutes - startMinutes + MINUTES_PER_DAY) % MINUTES_PER_DAY || MINUTES_PER_DAY;
        const elapsedMinutes = (valueMinutes - startMinutes + MINUTES_PER_DAY) % MINUTES_PER_DAY;

        return elapsedMinutes <= shiftDuration ? elapsedMinutes : null;
    }

    function isPastInShift(scheduleTime, nowMinutes, shiftStart, shiftEnd) {
        const scheduleElapsed = minutesFromShiftStart(scheduleTime, shiftStart, shiftEnd);
        const currentTime = `${Math.floor(nowMinutes / 60) % 24}:${String(Math.floor(nowMinutes % 60)).padStart(2, '0')}`;
        const currentElapsed = minutesFromShiftStart(currentTime, shiftStart, shiftEnd);

        return scheduleElapsed !== null
            && currentElapsed !== null
            && scheduleElapsed <= currentElapsed;
    }

    function getWebRows(rows, nowMinutes, isPersisted = hasRecordedRealization, shiftStart = null, shiftEnd = null) {
        return rows
            .map((ka, index) => ({ ka, index }))
            .filter(({ ka, index }) => ka.is_manual || (
                !isPersisted(ka, index)
                && (
                    isWithinNextHour(ka.jadwal, nowMinutes)
                    || isPastInShift(ka.jadwal, nowMinutes, shiftStart, shiftEnd)
                )
            ));
    }

    function getConfirmedRows(responseData, requestIndexes) {
        if (!Array.isArray(responseData)) {
            throw new Error('Server tidak mengonfirmasi realisasi yang tersimpan.');
        }

        const confirmedRows = new Map();

        responseData.forEach((item) => {
            const payloadIndex = Number(item?.index);
            const rowIndex = requestIndexes[payloadIndex];
            const realizationId = Number(item?.realisasi_ka_id);

            if (
                Number.isInteger(rowIndex)
                && Number.isInteger(realizationId)
                && realizationId > 0
            ) {
                confirmedRows.set(rowIndex, realizationId);
            }
        });

        if (requestIndexes.some((index) => !confirmedRows.has(index))) {
            throw new Error('Server belum mengonfirmasi seluruh realisasi. Data tetap ditampilkan; tekan Simpan untuk mencoba lagi.');
        }

        return confirmedRows;
    }

    function getShiftRows(rows) {
        return rows.map((ka, index) => ({ ka, index }));
    }

    function summarizeShiftRows(rows, getStatusColor) {
        const summary = {
            total: rows.length,
            tepat: 0,
            telat: 0,
            cepat: 0,
            batal: 0,
            belum: 0,
        };

        rows.forEach((ka) => {
            const color = getStatusColor(ka);

            if (color === 'tepat') {
                summary.tepat++;
            } else if (color === 'telat' || color === 'telat-tinggi') {
                summary.telat++;
            } else if (color === 'cepat') {
                summary.cepat++;
            } else if (color === 'batal') {
                summary.batal++;
            } else {
                summary.belum++;
            }
        });

        return summary;
    }

    function getDelayStatus(delayMinutes, settings = {}) {
        if (!Number.isFinite(delayMinutes)) {
            return 'tepat';
        }

        const normalLimit = Number(settings.toleransiKeterlambatanMenit ?? 5);
        const highLimit = Number(settings.peringatanKeterlambatanTinggiMenit ?? 10);

        if (settings.warningKeterlambatanTinggiAktif === true && delayMinutes >= highLimit) {
            return 'telat-tinggi';
        }

        if (settings.warningKeterlambatanAktif === true && delayMinutes > normalLimit) {
            return 'telat';
        }

        return 'tepat';
    }

    function getPrintDelayNote(delayMinutes, settings = {}) {
        if (!Number.isFinite(delayMinutes)) {
            return '';
        }

        if (delayMinutes <= 0) {
            return '';
        }

        const normalLimit = Number(settings.toleransiKeterlambatanMenit ?? 5);

        if (delayMinutes <= normalLimit) {
            return `Terlambat ${delayMinutes} menit (dalam toleransi)`;
        }

        const status = getDelayStatus(delayMinutes, settings);

        if (status === 'telat-tinggi') {
            return `Peringatan keterlambatan tinggi: ${delayMinutes} menit`;
        }

        if (status === 'telat') {
            return `Terlambat ${delayMinutes} menit`;
        }

        return `Terlambat ${delayMinutes} menit (peringatan nonaktif)`;
    }

    function getSimultaneousRowIndexes(rows) {
        return new Set(rows.flatMap((ka, index) => (
            ka.is_manual !== true && ka.is_bersamaan === true ? [index] : []
        )));
    }

    function getCheckPrintLabel(value) {
        if (value === 'yes') {
            return 'YA';
        }

        if (value === 'no') {
            return 'TIDAK';
        }

        return 'BELUM';
    }

    root.RealisasiKaTimeWindow = Object.freeze({
        getWindow,
        getWebRows,
        getShiftRows,
        getConfirmedRows,
        hasRecordedRealization,
        isPastInShift,
        isWithinNextHour,
        summarizeShiftRows,
        getDelayStatus,
        getPrintDelayNote,
        getSimultaneousRowIndexes,
        getCheckPrintLabel,
    });
})(window);
