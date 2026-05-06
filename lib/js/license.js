/**
 * AdminLicences — fonctions JavaScript
 * Extrait de AdminLicencesController.php (gridExtraFunction)
 * Correction : n.error → n.message dans proceedgetJsonFile
 */

function getCertificationFile(idMonth) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'getCertificationFile', idMonth: idMonth, ajax: true },
        async: false,
        dataType: 'json',
        success: function (data) {
            window.location.href = data.fileExport;
        }
    });
}

function generateJsonFile() {
    $('#content').addClass('page-is-changing');
    $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
        isAnimating = true;
        proceedJsonFile();
        $(this).off('transitionend webkitTransitionEnd');
    });
}

function proceedJsonFile() {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'generateJsonFile', ajax: true },
        async: true,
        success: function () {
            $('#content').removeClass('page-is-changing');
            $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
                isAnimating = false;
                $(this).off('transitionend webkitTransitionEnd');
            });
        }
    });
}

function generateFrontJsonFile() {
    $('html').addClass('csstransitions');
    isAnimating = true;
    $('#content').addClass('page-is-changing');
    $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
        proceedFrontJsonFile();
        $(this).off('transitionend webkitTransitionEnd');
    });
}

function proceedFrontJsonFile() {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'generateFrontJsonFile', ajax: true },
        async: false,
        complete: function () {
            $('#content').removeClass('page-is-changing');
            $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
                isAnimating = false;
                $(this).off('transitionend webkitTransitionEnd');
            });
        }
    });
}

function getNeededSuplies(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'getNeededSuplies', idLicense: idLicense, ajax: true },
        async: false,
        dataType: 'json',
        success: function (data) {
            window.location.href = data.fileExport;
        }
    });
}

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

function generateExpeditionFile() {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'generateExpeditionFile', ajax: true },
        async: false,
        dataType: 'json',
        success: function (data) {
            window.location.href = data.fileExport;
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
        // CORRIGÉ : n.error → n.message (cohérent avec le retour de License::getJsonFile())
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

function getFrontJsonFile(idLicense) {
    $('html').addClass('csstransitions');
    isAnimating = true;
    $('#content').addClass('page-is-changing');
    $('.cd-loading-bar').one('transitionend webkitTransitionEnd', function () {
        proceedgetFrontJsonFile(idLicense);
        $(this).off('transitionend webkitTransitionEnd');
    });
}

function proceedgetFrontJsonFile(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'getFrontJsonFile', idLicense: idLicense, ajax: true },
        async: false,
        dataType: 'json',
        success: function () {
            gridLicense.refreshDataAndView();
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

function editLicense(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'editLicence', idLicense: idLicense, ajax: true },
        async: false,
        dataType: 'json',
        success: function (data) {
            $('#license-edit').html(data.html);
            $('body').addClass('add');
            $('#license-edit').slideDown();
            $('#paragrid_AdminLicences').slideUp();
        }
    });
}

function updateFrontSite(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'updateFrontSite', idLicense: idLicense, ajax: true },
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

function synchSite(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'synchSite', idLicense: idLicense, ajax: true },
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

function autoUpdate() {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'autoUpdate', ajax: true },
        async: false,
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                showSuccessMessage(data.message);
                gridLicense.refreshDataAndView();
            } else {
                showErrorMessage(data.message);
            }
        }
    });
}

function synchTabs(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'synchTabs', idLicense: idLicense, ajax: true },
        async: true,
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                Swal.fire({ position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 4000 });
                $('#synch_tabs').val('0').selectmenu('refresh');
            } else {
                Swal.fire({ position: 'top-end', icon: 'error', title: data.message, showConfirmButton: false, timer: 4000 });
            }
        }
    });
}

function synchMetas(idLicense) {
    $.ajax({
        type: 'GET',
        url: AjaxLinkAdminLicences,
        data: { action: 'synchLicenseMetas', idLicense: idLicense, ajax: true },
        async: true,
        dataType: 'json',
        success: function (data) {
            if (data.success) {
                Swal.fire({ position: 'top-end', icon: 'success', title: data.message, showConfirmButton: false, timer: 4000 });
                $('#synch_metas').val('0').selectmenu('refresh');
            } else {
                Swal.fire({ position: 'top-end', icon: 'error', title: data.message, showConfirmButton: false, timer: 4000 });
            }
        }
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
