/*!
 * Sistema Académico UMG — comportamiento del lado del cliente.
 * Sin scripts en línea: compatible con la Content-Security-Policy del servidor.
 */
(() => {
    'use strict';

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    document.addEventListener('DOMContentLoaded', () => {
        initTooltips();
        initFlash();
        initFormValidation();
        initConfirmations();
        initPasswordToggles();
        initPasswordStrength();
        initAutosubmit();
        initUppercase();
        initDemoCredentials();
        initCapsLock();
        initPrint();
        initBack();
    });

    function initTooltips() {
        if (!window.bootstrap) return;
        $$('[data-bs-toggle="tooltip"]').forEach((el) => new bootstrap.Tooltip(el, { trigger: 'hover' }));
    }

    function initFlash() {
        $$('.flash[data-autohide]').forEach((el) => {
            const ms = parseInt(el.dataset.autohide, 10) || 5000;
            setTimeout(() => {
                if (window.bootstrap) bootstrap.Alert.getOrCreateInstance(el).close();
            }, ms);
        });
    }

    /** Validación HTML5 con estilos de Bootstrap (el servidor vuelve a validar). */
    function initFormValidation() {
        $$('form.needs-validation').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    const firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) firstInvalid.focus();
                } else {
                    const btn = form.querySelector('[type="submit"]');
                    if (btn) {
                        btn.disabled = true;
                        btn.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>');
                    }
                }
                form.classList.add('was-validated');
            });
        });
    }

    /** Confirmación modal para formularios destructivos (.js-confirm). */
    function initConfirmations() {
        const modalEl = $('#confirmModal');
        if (!modalEl || !window.bootstrap) return;

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        const text = $('#confirmModalText');
        const ok = $('#confirmModalOk');
        let pending = null;

        $$('form.js-confirm').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === '1') return;
                event.preventDefault();
                pending = form;
                text.textContent = form.dataset.confirm || 'Esta acción no se puede deshacer.';
                modal.show();
            });
        });

        ok.addEventListener('click', () => {
            if (!pending) return;
            pending.dataset.confirmed = '1';
            ok.disabled = true;
            pending.submit();
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            pending = null;
            ok.disabled = false;
        });
    }

    function initPasswordToggles() {
        $$('.js-toggle-password').forEach((btn) => {
            btn.addEventListener('click', () => {
                const input = document.getElementById(btn.dataset.target);
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                btn.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            });
        });
    }

    function initPasswordStrength() {
        $$('.js-strength').forEach((input) => {
            const meter = $(`.strength[data-for="${input.id}"] .strength-bar`);
            if (!meter) return;
            input.addEventListener('input', () => {
                const v = input.value;
                let score = 0;
                if (v.length >= 8) score++;
                if (v.length >= 12) score++;
                if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
                if (/\d/.test(v)) score++;
                if (/[^A-Za-z0-9]/.test(v)) score++;
                const colors = ['#dc3545', '#dc3545', '#fd7e14', '#f2b705', '#20c997', '#198754'];
                meter.style.width = `${(score / 5) * 100}%`;
                meter.style.backgroundColor = colors[score];
            });
        });
    }

    function initAutosubmit() {
        $$('.js-autosubmit').forEach((el) => el.addEventListener('change', () => el.form && el.form.submit()));
    }

    function initUppercase() {
        $$('input[data-uppercase]').forEach((el) => {
            el.addEventListener('input', () => {
                const pos = el.selectionStart;
                el.value = el.value.toUpperCase();
                el.setSelectionRange(pos, pos);
            });
        });
    }

    function initDemoCredentials() {
        $$('.demo-fill').forEach((btn) => {
            btn.addEventListener('click', () => {
                const u = $('#username');
                const p = $('#password');
                if (u && p) {
                    u.value = btn.dataset.user;
                    p.value = btn.dataset.pass;
                    p.focus();
                }
            });
        });
    }

    function initCapsLock() {
        const input = $('#password');
        const warning = $('#capsWarning');
        if (!input || !warning) return;
        const check = (e) => warning.classList.toggle('d-none', !(e.getModifierState && e.getModifierState('CapsLock')));
        input.addEventListener('keyup', check);
        input.addEventListener('keydown', check);
    }

    function initPrint() {
        $$('.js-print').forEach((btn) => btn.addEventListener('click', () => window.print()));
    }

    function initBack() {
        $$('.js-back').forEach((a) => a.addEventListener('click', (e) => {
            if (window.history.length > 1) {
                e.preventDefault();
                window.history.back();
            }
        }));
    }
})();
