/* Login & registration pages: show/hide password, live "passwords match" hint */
'use strict';

document.querySelectorAll('.auth-pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input   = document.getElementById(btn.dataset.target);
        const showing = input.type === 'text';
        input.type      = showing ? 'password' : 'text';
        btn.textContent = showing ? 'Show' : 'Hide';
    });
});

document.querySelectorAll('[data-match]').forEach(confirm => {
    const original = document.getElementById(confirm.dataset.match);
    const hint     = confirm.closest('.auth-group').querySelector('.auth-match');

    const check = () => {
        if (!confirm.value) { hint.textContent = ''; hint.className = 'auth-hint auth-match'; return; }
        const ok = confirm.value === original.value;
        hint.textContent = ok ? '✓ Passwords match' : '✗ Passwords do not match';
        hint.className   = 'auth-hint auth-match ' + (ok ? 'is-ok' : 'is-err');
    };
    confirm.addEventListener('input', check);
    original.addEventListener('input', check);
});
