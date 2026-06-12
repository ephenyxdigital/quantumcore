/**
 * AdminLicences — fonctions JavaScript
 * (Nettoyé : fonctions mortes retirées — voir audit ph_upgrader.)
 */

function cleanEmptyDirectory(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'cleanEmptyDirectory', idLicense: idLicense, ajax: true },
        async: false,
        dataType: 'json',
        success: function () {
            gridLicense.refreshDataAndView();
        }
    });
}

function updateVersion() {
    var version = $('#version').val();
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'updateVersion', version: version, ajax: true },
        async: false,
        dataType: 'json',
        success: function () {
            gridLicense.refreshDataAndView();
        }
    });
}

function upgradeVersion(idLicense) {
    var version = $('#version').val();
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'upgradeVersion', idLicense: idLicense, version: version, ajax: true },
        async: false,
        dataType: 'json',
        success: function () {
            gridLicense.refreshDataAndView();
        }
    });
}

function cleanUserCache(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'cleanUserCache', idLicense: idLicense, ajax: true },
        async: false,
        dataType: 'json',
        success: function () {
            gridLicense.refreshDataAndView();
        }
    });
}

function getJsonFile(idLicense) {
    $('#content').addClass('page-is-changing');
    $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
        isAnimating = true;
        proceedgetJsonFile(idLicense);
        $(this).off('transitionend webkitTransitionEnd');
    });
}

function proceedgetJsonFile(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'getJsonFile', idLicense: idLicense, ajax: true },
        async: true,
        dataType: 'json',
        success: function (data) {
            data.success ? gridLicense.refreshDataAndView() : showErrorMessage(data.message);
        },
        complete: function () {
            $('#content').removeClass('page-is-changing');
            $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
                isAnimating = false;
                $(this).off('transitionend webkitTransitionEnd');
            });
        }
    });
}

function updateSite(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'updateSite', idLicense: idLicense, ajax: true },
        async: false,
        dataType: 'json',
        success: function (data) {
            $('#updateSite').html(data.html);
            $('body').addClass('add');
            $('#updateSite').slideDown();
            $('#paragrid_AdminLicences').slideUp();
        }
    });
}

function generateRelease() {
    Swal.fire({
        title: 'Générer une release',
        html:
            '<input id="rel_version" class="swal2-input" placeholder="Version (ex. 1.8.6.11)">' +
            '<select id="rel_channel" class="swal2-input"><option value="stable">stable</option><option value="rc">rc</option></select>' +
            '<textarea id="rel_changelog" class="swal2-textarea" placeholder="Notes de version (optionnel)"></textarea>' +
            '<label style="display:block;margin-top:8px;text-align:left;font-size:13px;"><input type="checkbox" id="rel_refresh" checked> Régénérer l\'empreinte des fichiers (après modif du cœur)</label>',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Générer et publier',
        cancelButtonText: 'Annuler',
        preConfirm: function () {
            var v = (document.getElementById('rel_version').value || '').trim();
            if (!v) {
                Swal.showValidationMessage('Version requise');
                return false;
            }
            return {
                version: v,
                channel: document.getElementById('rel_channel').value,
                changelog: document.getElementById('rel_changelog').value,
                refresh: document.getElementById('rel_refresh').checked ? 1 : 0
            };
        }
    }).then(function (res) {
        if (!res.value) {
            return;
        }
        $('#content').addClass('page-is-changing');
        $.ajax({
            type: 'POST',
            url: AjaxLinkAdminLicences,
            data: {
                action: 'generateRelease',
                ajax: true,
                version: res.value.version,
                channel: res.value.channel,
                changelog: res.value.changelog,
                refresh: res.value.refresh
            },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    Swal.fire({ position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 5000 });
                    gridLicense.refreshDataAndView();
                } else {
                    Swal.fire({ icon: 'error', title: 'Échec', text: data.message });
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Erreur réseau pendant la génération' });
            },
            complete: function () {
                $('#content').removeClass('page-is-changing');
            }
        });
    });
}

function enrollPublicKey(idLicense) {
    Swal.fire({
        title: 'Enrôler la clé publique',
        html:
            '<p style="font-size:13px;text-align:left;">Colle ici la clé publique Ed25519 affichée sur le site client (onglet « Mises à jour »).</p>' +
            '<textarea id="enr_pubkey" class="swal2-textarea" placeholder="clé publique base64"></textarea>',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Enrôler',
        cancelButtonText: 'Annuler',
        preConfirm: function () {
            var k = (document.getElementById('enr_pubkey').value || '').trim();
            if (!k) {
                Swal.showValidationMessage('Clé publique requise');
                return false;
            }
            return { public_key: k };
        }
    }).then(function (res) {
        if (!res.value) {
            return;
        }
        $.post(AjaxLinkAdminLicences, {
            ajax: true,
            action: 'setPublicKey',
            idLicense: idLicense,
            public_key: res.value.public_key
        }, null, 'json')
            .done(function (data) {
                if (data.success) {
                    Swal.fire({ position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 4000 });
                    gridLicense.refreshDataAndView();
                } else {
                    Swal.fire({ icon: 'error', title: 'Échec', text: data.message });
                }
            })
            .fail(function () {
                Swal.fire({ icon: 'error', title: 'Erreur réseau' });
            });
    });
}

$(document).ready(function () {
    $('#synch_tabs').selectmenu({
        width: 450,
        icons: { button: 'fa-duotone fa-regular fa-bars' },
        change: function (event, ui) {
            if (ui.item.value > 0) {
                synchTabs(ui.item.value);
            }
        }
    });

    $('#synch_metas').selectmenu({
        width: 350,
        icons: { button: 'fa-duotone fa-regular fa-bars' },
        change: function (event, ui) {
            if (ui.item.value > 0) {
                synchMetas(ui.item.value);
            }
        }
    });
});
