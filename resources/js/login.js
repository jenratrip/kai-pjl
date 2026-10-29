
(function () {
    const EYE = `<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle>`;
    const EYE_OFF = `<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><line x1="4" y1="4" x2="20" y2="20"></line>`;

    document.querySelectorAll('.password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(btn.dataset.target);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            btn.querySelector('svg').innerHTML = show ? EYE_OFF : EYE;
        });
    });

    const form = document.getElementById('loginForm');
    if (!form) return;

    const nipInput = document.getElementById('nip');
    const passInput = document.getElementById('password');
    const pjlFields = document.getElementById('pjlFields');
    const submitBtn = form.querySelector('.login-button');
    const errorBox = document.getElementById('clientError');
    const token = form.querySelector('input[name="_token"]').value;
    const cekUrl = form.dataset.cekUrl;

    function setPjl(show) {
        pjlFields.hidden = !show;
        pjlFields.disabled = !show;
    }

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.hidden = false;
    }

    [nipInput, passInput].forEach(function (el) {
        el.addEventListener('input', function () {
            setPjl(false);
        });
    });

    form.addEventListener('submit', async function (e) {
        if (!pjlFields.hidden) return;

        e.preventDefault();
        errorBox.hidden = true;
        submitBtn.disabled = true;

        try {
            const res = await fetch(cekUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    nip: nipInput.value,
                    password: passInput.value,
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                showError(data.message || 'NIPP / NIPKWT atau password tidak valid.');
                return;
            }

            if (data.role === 'pjl') {
                setPjl(true);
                document.getElementById('nomorPjl').focus();
            } else {
                form.submit();
            }
        } catch (err) {
            form.submit();
        } finally {
            submitBtn.disabled = false;
        }
    });
})();
