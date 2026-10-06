/* FC Scorpio — small progressive-enhancement scripts (no dependencies). */
(function () {
    'use strict';
    const $ = (s, c = document) => c.querySelector(s);
    const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

    // Mobile navigation & admin sidebar
    $$('[data-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = $(btn.dataset.toggle);
            if (!target) return;
            const open = target.classList.toggle('open');
            btn.setAttribute('aria-expanded', open);
        });
    });

    // Dropdown menus
    $$('.dropdown').forEach(dd => {
        const toggle = $('.dropdown-toggle', dd);
        toggle && toggle.addEventListener('click', e => {
            e.stopPropagation();
            dd.classList.toggle('open');
        });
    });
    document.addEventListener('click', () => $$('.dropdown.open').forEach(d => d.classList.remove('open')));

    // Flash messages: close button + auto-dismiss
    $$('.flash').forEach(f => {
        const close = () => { f.style.transition = 'opacity .3s, transform .3s'; f.style.opacity = 0; f.style.transform = 'translateY(-6px)'; setTimeout(() => f.remove(), 300); };
        $('button', f)?.addEventListener('click', close);
        setTimeout(close, 6000);
    });

    // Confirm before destructive actions: <form data-confirm="Are you sure?">
    $$('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Quantity steppers
    $$('.qty').forEach(q => {
        const input = $('input', q);
        $$('button[data-step]', q).forEach(b => b.addEventListener('click', () => {
            const min = +input.min || 1, max = +input.max || 99;
            input.value = Math.min(max, Math.max(min, (+input.value || min) + (+b.dataset.step)));
            input.dispatchEvent(new Event('change'));
        }));
    });

    // Match countdown: <div class="countdown" data-kickoff="ISO date">
    $$('.countdown[data-kickoff]').forEach(el => {
        const target = new Date(el.dataset.kickoff).getTime();
        const parts = ['d', 'h', 'm', 's'].map(k => $(`[data-unit="${k}"]`, el));
        const tick = () => {
            let diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
            const vals = [Math.floor(diff / 86400), Math.floor(diff % 86400 / 3600), Math.floor(diff % 3600 / 60), diff % 60];
            vals.forEach((v, i) => parts[i] && (parts[i].textContent = String(v).padStart(2, '0')));
        };
        tick();
        setInterval(tick, 1000);
    });

    // Client-side filter tabs: <button class="tab" data-filter="Forward">
    $$('[data-filter-group]').forEach(group => {
        const scope = $(group.dataset.filterGroup);
        $$('.tab', group).forEach(tab => tab.addEventListener('click', () => {
            $$('.tab', group).forEach(t => t.classList.toggle('active', t === tab));
            const f = tab.dataset.filter;
            $$('[data-group]', scope).forEach(sec => { sec.hidden = f !== 'all' && sec.dataset.group !== f; });
        }));
    });

    // Live preview for image uploads
    $$('input[type=file][data-preview]').forEach(input => {
        input.addEventListener('change', () => {
            const img = $(input.dataset.preview);
            const file = input.files && input.files[0];
            if (img && file) { img.src = URL.createObjectURL(file); img.hidden = false; }
        });
    });

    // Ticket tier live total
    $$('[data-unit-price]').forEach(form => {
        const input = $('input[name=quantity]', form), out = $('[data-total]', form);
        const price = +form.dataset.unitPrice;
        const upd = () => out && (out.textContent = '৳' + (price * (+input.value || 0)).toLocaleString('en-US'));
        input.addEventListener('change', upd); input.addEventListener('input', upd); upd();
    });
})();
