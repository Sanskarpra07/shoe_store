/* StepStyle - Auto-dismiss status messages after 4 seconds */
document.addEventListener('DOMContentLoaded', function () {
    var msgs = document.querySelectorAll('.msg-success, .msg-error, .msg-info, .alert');
    setTimeout(function () {
        msgs.forEach(function (m) {
            m.style.transition = 'opacity 0.5s ease';
            m.style.opacity = '0';
            setTimeout(function () { m.style.display = 'none'; }, 500);
        });
    }, 4000);
});