function updateFile() {

	editor.setValue('', -1);
	var totalFiles = fileMustUpdates.length;
	var current = 0;
	var row = 0;
	var fileToUpdate = totalFiles;
	$('#h1-ace').html('Mise à jour des fichiers distants <span id="ace-countdonwn">' + fileToUpdate + '</span> à traiter');
	$('.ace-container').slideDown();
	$('#mustUpdate').slideUp();
	$('#ajax_running').slideDown();
	$.ajax({
		url: AjaxLinkAdminSynch,
		type: "POST",
		data: {
			action: 'upgradeFile',
			files: JSON.stringify(fileMustUpdates),
			ajax: true
		},
		async: true,
		dataType: "json",
		success: function success(response) {
			$.each(response, function(key, index) {
				setTimeout(function() {
					editor.session.insert({
						row: row,
						column: 0
					}, index.message + "\n")
					row++;
					fileToUpdate--;
					$('#ace-countdonwn').html(fileToUpdate);
					editor.scrollToLine(row, true, true, function() {});
					editor.gotoLine(row, 0, true);
					current++;
					if (current == totalFiles) {
						fileMustUpdates = [];
						$('#ajax_running').slideUp();
						editor.session.insert({
							row: current,
							column: 0
						}, "\n" + index.message + "\n")

						if (fileMustBeCreates.length > 0) {
							$('#mustCreate').slideDown();
						} else if (fileMustBeDeletes.length > 0) {
							$('#mustDelete').slideDown();
						} else {
							$('#allaParamsAreOk').slideDown();
							setTimeout(function() {
								$('#content').slideDown();
								$('#synchronArea').slideUp();
								$('#synchronArea').html('');
							}, 5000);
						}
					}
				}, 2000)
			});
		}
	});

}

function createFile() {

	editor.setValue('', -1);
	var totalFiles = fileMustBeCreates.length;
	var current = 0;
	var row = 0;
	var fileToCreate = totalFiles;
	$('#h1-ace').html('Création des fichiers distants <span id="ace-countdonwn">' + fileToCreate + '</span> à traiter');
	$('.ace-container').slideDown();
	$('#mustCreate').slideUp();
	$('#ajax_running').slideDown();

	$.ajax({
		url: AjaxLinkAdminSynch,
		type: "POST",
		data: {
			action: 'createFile',
			files: JSON.stringify(fileMustBeCreates),
			ajax: true
		},
		async: true,
		dataType: "json",
		success: function success(response) {
			$.each(response, function(key, index) {
				setTimeout(function() {
					editor.session.insert({
						row: row,
						column: 0
					}, index.message + "\n")
					row++;
					fileToCreate--;
					$('#ace-countdonwn').html(fileToCreate);
					editor.scrollToLine(row, true, true, function() {});
					editor.gotoLine(row, 0, true);
					current++;
					if (current == totalFiles) {
						fileMustBeCreates = [];
						$('#ajax_running').slideUp();
						editor.session.insert({
							row: current,
							column: 0
						}, "\n" + index.message + "\n")

						if (fileMustBeDeletes.length > 0) {
							$('#mustDelete').slideDown();
						} else {
							$('#allaParamsAreOk').slideDown();
							setTimeout(function() {
								$('#content').slideDown();
								$('#synchronArea').slideUp();
								$('#synchronArea').html('');
							}, 5000);
						}
					}
				}, 2000)
			});
		}
	});


}

function deleteFile() {

	editor.setValue('', -1);
	var totalFiles = fileMustBeDeletes.length;
	var current = 0;
	var row = 0;
	var fileToDelete = totalFiles;
	$('#h1-ace').html('Suppression des fichiers distants <span id="ace-countdonwn">' + fileToDelete + '</span> à traiter');
	$('.ace-container').slideDown();
	$('#mustDelete').slideUp();
	$('#ajax_running').slideDown();

	$.ajax({
		url: AjaxLinkAdminSynch,
		type: "POST",
		cache: false,
		data: {
			action: 'deleteFile',
			files: JSON.stringify(fileMustBeDeletes),
			ajax: true
		},
		async: true,
		dataType: "json",
		success: function success(response) {
			$.each(response, function(key, index) {
				setTimeout(function() {
					editor.session.insert({
						row: row,
						column: 0
					}, index.message + "\n")
					row++;
					fileToDelete--;
					$('#ace-countdonwn').html(fileToDelete);
					editor.scrollToLine(row, true, true, function() {});
					editor.gotoLine(row, 0, true);
					current++;
					if (current == totalFiles) {
						fileMustBeDeletes = [];
						$('#ajax_running').slideUp();
						editor.session.insert({
							row: current,
							column: 0
						}, "\n" + index.message + "\n")
						$('#allaParamsAreOk').slideDown();
						setTimeout(function() {
							$('#content').slideDown();
							$('#synchronArea').slideUp();
							$('#synchronArea').html('');
						}, 5000);

					}
				}, 2000)
			});
		}
	});

}

function regenerateVersion() {

	$.ajax({
		type: 'GET',
		url: AjaxLinkAdminSynch,
		data: {
			action: 'regenerateVersion',
			ajax: true
		},
		async: false,
		dataType: 'json'
	});
}