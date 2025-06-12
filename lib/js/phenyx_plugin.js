function scanPlugin() {
    
    $.ajax({
			type: 'POST',
			url: AjaxLinkAdminPhenyxPlugins,
			data: {
				action: 'scanPlugin',
				ajax: true
			},
			dataType: 'json',
			success: function(data) {
                if (data.success) {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });	
				    gridPhenyxPlugins.refreshDataAndView();
                }
			}
		});
}