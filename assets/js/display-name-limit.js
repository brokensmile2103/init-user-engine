/**
 * Init User Engine – display name change limit on wp-admin/profile.php.
 *
 * Shows the limit under the "Display name publicly as" field and disables the
 * field while the member has to wait. The server always checks the limit too.
 */
(function () {
    'use strict';

    function init() {
        var data   = window.InitUserEngineDisplayNameLimit || {};
        var select = document.getElementById('display_name');

        if (!select || !data.notice) {
            return;
        }

        if (data.locked) {
            // A disabled field is not submitted, so the current display name is kept.
            select.disabled = true;
        }

        var note = document.createElement('p');
        note.className = 'description iue-display-name-limit-note';
        note.id = 'iue-display-name-limit-note';
        note.textContent = data.notice;

        select.setAttribute('aria-describedby', note.id);
        select.parentNode.appendChild(note);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
