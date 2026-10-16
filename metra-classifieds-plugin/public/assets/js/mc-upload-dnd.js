/*
 * METRA Classifieds: drag & drop upload enhancement.
 *
 * Purpose:
 * - Keep the original native file input in place for WordPress/PHP upload handling.
 * - Add a modern drag & drop surface on top of that native input.
 * - Avoid changing form names or backend upload logic.
 *
 * This script enhances any wrapper marked with:
 *   .mc-upload-dnd
 * and expects one file input with:
 *   .mc-upload-dnd__input
 */
(function () {
    'use strict';

    function t(key, fallback) {
        if (window.MC_UPLOAD_DND && window.MC_UPLOAD_DND.i18n && window.MC_UPLOAD_DND.i18n[key]) {
            return window.MC_UPLOAD_DND.i18n[key];
        }
        return fallback;
    }

    function formatSelected(count) {
        return t('selectedFiles', '%d file(s) selected').replace('%d', String(count));
    }

    function setFiles(input, files) {
        try {
            var transfer = new DataTransfer();
            Array.prototype.forEach.call(files, function (file) {
                transfer.items.add(file);
            });
            input.files = transfer.files;
            return true;
        } catch (err) {
            return false;
        }
    }

    function renderFileList(listEl, files) {
        listEl.innerHTML = '';
        if (!files || !files.length) {
            return;
        }

        Array.prototype.forEach.call(files, function (file) {
            var item = document.createElement('li');
            item.textContent = file.name;
            listEl.appendChild(item);
        });
    }

    function bindDropzone(root) {
        var input = root.querySelector('.mc-upload-dnd__input');
        var surface = root.querySelector('.mc-upload-dnd__surface');
        var button = root.querySelector('.mc-upload-dnd__button');
        var status = root.querySelector('.mc-upload-dnd__status');
        var list = root.querySelector('.mc-upload-dnd__list');

        if (!input || !surface || !button || !status || !list) {
            return;
        }

        function syncFromInput() {
            var files = input.files;
            status.textContent = files && files.length ? formatSelected(files.length) : t('noFiles', 'No files selected');
            renderFileList(list, files);
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            input.click();
        });

        surface.addEventListener('click', function (event) {
            if (event.target === button) {
                return;
            }
            input.click();
        });

        input.addEventListener('change', syncFromInput);

        ['dragenter', 'dragover'].forEach(function (type) {
            surface.addEventListener(type, function (event) {
                event.preventDefault();
                surface.classList.add('is-dragover');
            });
        });

        ['dragleave', 'dragend', 'drop'].forEach(function (type) {
            surface.addEventListener(type, function (event) {
                event.preventDefault();
                surface.classList.remove('is-dragover');
            });
        });

        surface.addEventListener('drop', function (event) {
            var files = event.dataTransfer ? event.dataTransfer.files : null;
            if (!files || !files.length) {
                return;
            }

            if (setFiles(input, files)) {
                syncFromInput();
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        syncFromInput();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var zones = document.querySelectorAll('.mc-upload-dnd');
        Array.prototype.forEach.call(zones, bindDropzone);
    });
}());
