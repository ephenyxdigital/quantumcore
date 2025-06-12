
function synchroniseIo() {
	
	$.ajax({
		type: 'GET',
		url: AjaxLinkAdminSynch,
		data: {
			action: 'synchroEphenyxIo',
			ajax: true
		},
		async: false,
		dataType: 'json',
		success: function success(data) {
			if (data.success) {
            	showSuccessMessage(data.message);	
				$('#synchronArea').html(data.html);
				$('#content').slideUp();
				$('#synchronArea').slideDown();
			}
		}
	});
}

