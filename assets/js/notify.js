/* StepStyle - SweetAlert2 notifications for flash messages and confirm dialogs */
(function () {
    if (typeof Swal === 'undefined') {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var flashTypes = {
            'swal-success': 'success',
            'swal-error': 'error',
            'msg-success': 'success',
            'msg-error': 'error',
            'msg-info': 'info',
            'alert-success': 'success',
            'alert-danger': 'error',
            'alert-warning': 'warning',
            'alert-info': 'info'
        };

        document.querySelectorAll('.swal-success, .swal-error, .msg-success, .msg-error, .msg-info, .alert-success, .alert-danger, .alert-warning, .alert-info').forEach(function (msg) {
            if (msg.querySelector('a, button, input, select, textarea')) {
                return;
            }
            var icon = flashTypes[msg.className.split(/\s+/).find(function (c) { return flashTypes[c]; })] || 'info';
            var text = msg.textContent.trim();
            if (!text) {
                return;
            }
            var timerDuration = 15000;
            Swal.fire({
                position: 'top-end',
                icon: icon,
                title: text,
                showConfirmButton: false,
                timer: timerDuration,
                timerProgressBar: true,
                toast: true,
                html: '<span style="font-size:13px;">' + text + '</span><br><small id="swal-timer-text" style="opacity:0.7;font-size:11px;"></small>',
                didOpen: function () {
                    var timerEl = document.getElementById('swal-timer-text');
                    var remaining = timerDuration;
                    var interval = setInterval(function () {
                        remaining -= 100;
                        if (remaining <= 0) {
                            clearInterval(interval);
                            return;
                        }
                        if (timerEl) {
                            timerEl.textContent = Math.ceil(remaining / 1000) + 's';
                        }
                    }, 100);
                }
            });
            if (msg.closest && msg.closest('form')) {
                /* keep live validation blocks in the DOM */
            } else {
                msg.style.display = 'none';
            }
        });

        /* Replace native confirm() with a SweetAlert2 confirmation modal */
        document.addEventListener('submit', function (e) {
            var form = e.target;
            var message = form.getAttribute('data-confirm');
            if (!message) {
                return;
            }
            e.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.removeAttribute('data-confirm');
                    form.submit();
                }
            });
        });

        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) {
                return;
            }
            e.preventDefault();
            var message = el.getAttribute('data-confirm');
            var form = el.closest('form');
            if (form && el.type === 'submit') {
                Swal.fire({
                    title: 'Are you sure?',
                    text: message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'Cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        el.removeAttribute('data-confirm');
                        form.submit();
                    }
                });
                return;
            }
            var href = el.getAttribute('href');
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes',
                cancelButtonText: 'Cancel'
            }).then(function (result) {
                if (result.isConfirmed) {
                    window.location.href = href;
                }
            });
        });
    });
})();
