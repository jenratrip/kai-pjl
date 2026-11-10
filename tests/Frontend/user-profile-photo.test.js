import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import {
    initializeUserProfilePhoto,
    validateProfilePhoto,
} from '../../resources/js/user-profile-photo.js';

const view = readFileSync(
    new URL('../../resources/views/user/profile.blade.php', import.meta.url),
    'utf8',
);
const sidebar = readFileSync(
    new URL('../../resources/views/components/user-operasional/sidebar.blade.php', import.meta.url),
    'utf8',
);
const header = readFileSync(
    new URL('../../resources/views/components/user-operasional/header.blade.php', import.meta.url),
    'utf8',
);
const layoutCss = readFileSync(
    new URL('../../public/css/user-operasional/layout.css', import.meta.url),
    'utf8',
);

test('profile photo preview validation allows JPEG, PNG and WebP up to two megabytes', () => {
    for (const type of ['image/jpeg', 'image/png', 'image/webp']) {
        assert.equal(validateProfilePhoto({ type, size: 2 * 1024 * 1024 }), '');
    }
});

test('profile photo preview validation rejects missing, unsupported and oversized files', () => {
    assert.equal(validateProfilePhoto(null), 'Pilih foto profil terlebih dahulu.');
    assert.equal(validateProfilePhoto({ type: 'image/gif', size: 1 }), 'Foto harus berformat JPEG, PNG, atau WebP.');
    assert.equal(validateProfilePhoto({ type: 'image/jpeg', size: 2 * 1024 * 1024 + 1 }), 'Ukuran foto maksimal 2 MB.');
});

test('profile page includes a private upload form, preview and protected current-user avatar', () => {
    assert.match(view, /enctype="multipart\/form-data"/);
    assert.match(view, /data-profile-photo-preview/);
    assert.match(view, /data-profile-photo-modal[\s\S]*items-center justify-center/);
    assert.match(view, /data-profile-photo-cancel/);
    assert.match(view, /route\('user\.profile\.password\.update'\)/);
    assert.match(view, /<x-user-operasional\.user-avatar/);
});

test('profile keeps identity and photo on the left and account security on the right on desktop', () => {
    assert.match(view, /grid items-start gap-5 lg:grid-cols-2/);
    assert.match(view, /Informasi Petugas[\s\S]*Foto Profil[\s\S]*Keamanan Akun/);
    assert.match(view, /<x-user-operasional\.user-avatar/);
    assert.match(view, /route\('user\.profile\.password\.update'\)/);
});

test('PJL navigation places the avatar beside the username and keeps header identity next to a right-side avatar', () => {
    assert.doesNotMatch(sidebar, /Ubah foto profil/);
    assert.match(sidebar, /op-active-user-profile[\s\S]*user-avatar[\s\S]*op-active-user-name/);
    assert.match(header, /op-user-info[\s\S]*op-header-avatar[\s\S]*user-avatar/);
    assert.match(layoutCss, /\.op-active-user-profile\s*\{[\s\S]*?display:\s*flex/);
    assert.match(layoutCss, /@media \(max-width: 768px\)[\s\S]*?\.op-header-avatar/);
});

test('selecting a valid profile photo displays a local preview without submitting the form', () => {
    const listeners = new Map();
    const classes = new Set(['hidden']);
    const input = {
        files: [{ type: 'image/png', size: 100 }],
        focus() {},
        addEventListener: (name, listener) => listeners.set(`input:${name}`, listener),
    };
    const preview = {
        src: '',
        removeAttribute(name) {
            if (name === 'src') this.src = '';
        },
    };
    const wrapper = {
        classList: {
            add: (name) => classes.add(name),
            remove: (name) => classes.delete(name),
        },
    };
    const error = { textContent: '', hidden: true };
    const formListeners = new Map();
    const form = {
        dataset: {},
        querySelector(selector) {
            return {
                '[data-profile-photo-input]': input,
                '[data-profile-photo-preview]': preview,
                '[data-profile-photo-preview-wrap]': wrapper,
                '[data-profile-photo-error]': error,
            }[selector];
        },
        addEventListener: (name, listener) => formListeners.set(name, listener),
    };
    const modalClasses = new Set(['hidden']);
    const modalListeners = new Map();
    const modal = {
        dataset: {},
        classList: {
            add: (name) => modalClasses.add(name),
            remove: (name) => modalClasses.delete(name),
            contains: (name) => modalClasses.has(name),
        },
        querySelector(selector) {
            return {
                '[data-profile-photo-close]': { addEventListener: (name, listener) => modalListeners.set(`close:${name}`, listener) },
                '[data-profile-photo-cancel]': { addEventListener: (name, listener) => modalListeners.set(`cancel:${name}`, listener) },
            }[selector];
        },
        addEventListener: (name, listener) => modalListeners.set(`modal:${name}`, listener),
    };
    const openListeners = new Map();
    const openButton = { addEventListener: (name, listener) => openListeners.set(name, listener) };
    const bodyClasses = new Set();
    const documentListeners = new Map();
    const previousDocument = globalThis.document;
    const previousWindow = globalThis.window;
    const originalCreateObjectURL = URL.createObjectURL;
    const originalRevokeObjectURL = URL.revokeObjectURL;
    URL.createObjectURL = () => 'blob:profile-preview';
    URL.revokeObjectURL = () => {};
    globalThis.document = {
        querySelector: (selector) => selector === '[data-profile-photo-form]' ? form
            : selector === '[data-profile-photo-modal]' ? modal : openButton,
        activeElement: openButton,
        body: { classList: {
            add: (name) => bodyClasses.add(name),
            remove: (name) => bodyClasses.delete(name),
        } },
        addEventListener: (name, listener) => documentListeners.set(name, listener),
    };
    globalThis.window = { addEventListener: () => {} };

    try {
        initializeUserProfilePhoto();
        listeners.get('input:change')();

        assert.equal(preview.src, 'blob:profile-preview');
        assert.equal(classes.has('hidden'), false);
        assert.equal(error.hidden, true);

        let prevented = false;
        formListeners.get('submit')({ preventDefault: () => { prevented = true; } });
        assert.equal(prevented, false);

        openListeners.get('click')();
        assert.equal(modalClasses.has('hidden'), false);
        assert.equal(modalClasses.has('flex'), true);
        assert.equal(bodyClasses.has('overflow-hidden'), true);

        modalListeners.get('cancel:click')();
        assert.equal(modalClasses.has('hidden'), true);
        assert.equal(bodyClasses.has('overflow-hidden'), false);
        assert.equal(input.value, '');
        assert.equal(classes.has('hidden'), true);
    } finally {
        globalThis.document = previousDocument;
        globalThis.window = previousWindow;
        URL.createObjectURL = originalCreateObjectURL;
        URL.revokeObjectURL = originalRevokeObjectURL;
    }
});
