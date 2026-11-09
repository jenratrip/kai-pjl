import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    bindConditionChange,
    closeEvidenceDialog,
    openEvidenceDialog,
    requiresEvidenceModal,
    routeInspectionAction,
    validateEvidenceInput,
} from '../../resources/js/inventaris-inspection.js';

const blade = readFileSync(
    new URL('../../resources/views/user/inventaris/index.blade.php', import.meta.url),
    'utf8',
);
const script = readFileSync(
    new URL('../../resources/js/inventaris-inspection.js', import.meta.url),
    'utf8',
);

test('Baik saves through the existing direct action while Rusak and Hilang require confirmation modal', () => {
    const actions = [];

    assert.equal(requiresEvidenceModal('baik'), false);
    assert.equal(requiresEvidenceModal('perlu_perbaikan'), false);
    assert.equal(requiresEvidenceModal('rusak'), true);
    assert.equal(requiresEvidenceModal('hilang'), true);

    assert.equal(routeInspectionAction('baik', {
        openEvidenceModal: () => actions.push('modal'),
        saveImmediately: (condition) => actions.push(`save:${condition}`),
    }), 'save');
    assert.equal(routeInspectionAction('rusak', {
        openEvidenceModal: (condition) => actions.push(`modal:${condition}`),
        saveImmediately: (condition) => actions.push(`save:${condition}`),
    }), 'modal');
    assert.equal(routeInspectionAction('hilang', {
        openEvidenceModal: (condition) => actions.push(`modal:${condition}`),
        saveImmediately: (condition) => actions.push(`save:${condition}`),
    }), 'modal');

    assert.deepEqual(actions, ['save:baik', 'modal:rusak', 'modal:hilang']);
});

test('Rusak requires a description and photo while Hilang requires only a description', () => {
    assert.deepEqual(validateEvidenceInput('rusak', '', null, false), {
        catatan: 'Keterangan kerusakan atau kehilangan wajib diisi.',
        bukti_foto: 'Foto bukti kerusakan wajib dilampirkan.',
    });
    assert.deepEqual(validateEvidenceInput('rusak', 'Ada retak.', null, false), {
        bukti_foto: 'Foto bukti kerusakan wajib dilampirkan.',
    });
    assert.deepEqual(validateEvidenceInput('rusak', 'Ada retak.', null, true), {});
    assert.deepEqual(validateEvidenceInput('hilang', 'Tidak ditemukan.', null, false), {});
    assert.deepEqual(validateEvidenceInput('hilang', '', null, false), {
        catatan: 'Keterangan kerusakan atau kehilangan wajib diisi.',
    });
});

test('evidence validation restricts image formats and size to five megabytes', () => {
    assert.deepEqual(validateEvidenceInput('hilang', 'Tidak ditemukan.', {
        type: 'image/gif',
        size: 10,
    }, false), {
        bukti_foto: 'Foto harus berformat JPEG, PNG, atau WebP.',
    });
    assert.deepEqual(validateEvidenceInput('hilang', 'Tidak ditemukan.', {
        type: 'image/jpeg',
        size: 5 * 1024 * 1024 + 1,
    }, false), {
        bukti_foto: 'Ukuran foto maksimal 5 MB.',
    });
    assert.deepEqual(validateEvidenceInput('hilang', 'Tidak ditemukan.', {
        type: 'image/webp',
        size: 5 * 1024 * 1024,
    }, false), {});
});

test('changing condition opens the evidence modal for Rusak and Hilang but not Baik', () => {
    const listeners = new Map();
    const conditionInput = {
        value: 'baik',
        addEventListener: (event, listener) => listeners.set(event, listener),
    };
    const opened = [];
    const pending = [];

    bindConditionChange(conditionInput, {
        openEvidenceModal: (condition) => opened.push(condition),
        markPending: (condition) => pending.push(condition),
    });

    for (const condition of ['rusak', 'hilang', 'baik']) {
        conditionInput.value = condition;
        listeners.get('change')();
    }

    assert.deepEqual(opened, ['rusak', 'hilang']);
    assert.deepEqual(pending, ['baik']);
});

test('evidence dialog works with native dialog methods and an open-attribute fallback', () => {
    const nativeCalls = [];
    openEvidenceDialog({ showModal: () => nativeCalls.push('open') });
    closeEvidenceDialog({ close: () => nativeCalls.push('close') });
    assert.deepEqual(nativeCalls, ['open', 'close']);

    const attributes = new Set();
    const fallbackDialog = {
        setAttribute: (name) => attributes.add(name),
        removeAttribute: (name) => attributes.delete(name),
    };

    openEvidenceDialog(fallbackDialog);
    assert.equal(attributes.has('open'), true);
    closeEvidenceDialog(fallbackDialog);
    assert.equal(attributes.has('open'), false);
});

test('the modal keeps confirmation and cancel separate and sends no request from its inputs', () => {
    assert.match(blade, /data-evidence-cancel[\s\S]*>Batal<\/button>/);
    assert.match(blade, /data-evidence-confirm[\s\S]*>Simpan<\/button>/);
    assert.match(blade, /data-evidence-preview/);

    const cancelStart = script.indexOf("dialog.querySelector('[data-evidence-cancel]')");
    const confirmStart = script.indexOf("dialog.querySelector('[data-evidence-confirm]').addEventListener");
    const cancelHandler = script.slice(cancelStart, confirmStart);
    const inputHandlers = script.slice(
        script.indexOf("descriptionInput.addEventListener('input'"),
        script.indexOf("window.addEventListener('pagehide'"),
    );
    const confirmHandler = script.slice(
        confirmStart,
        script.indexOf("descriptionInput.addEventListener('input'"),
    );
    const cardHandlers = script.slice(
        script.indexOf('cards.forEach((card) => {'),
        cancelStart,
    );

    assert.match(cancelHandler, /cancelModal\)/);
    assert.doesNotMatch(cancelHandler, /fetch\s*\(|save\s*\(/);
    assert.doesNotMatch(inputHandlers, /fetch\s*\(|save\s*\(/);
    assert.match(confirmHandler, /save\(activeCard, pendingCondition/);
    assert.match(cardHandlers, /form.addEventListener\('submit'/);
    assert.match(cardHandlers, /bindConditionChange\(conditionInput/);
    assert.doesNotMatch(cardHandlers, /fetch\s*\(|setTimeout\s*\(|addEventListener\('input'/);
});

