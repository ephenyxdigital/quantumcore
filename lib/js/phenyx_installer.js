var srcCustomer;
function launchInstallProcess(idInstance) {
    
    $.ajax({
		type: 'GET',
		url: AjaxLinkAdminPhenyxInstaller,
		data: {
			action: 'launchInstallProcess',
			idInstance: idInstance,
			ajax: true
		},
		async: false,
		dataType: 'json',
		success: function success(data) {
			$('#tabs-AdminDashboard').append(data.li);
           	$('#tabs-AdminDashboard-content').append(data.html);
			$('#content_AdminDashboard').slideDown();
            $("#content_AdminDashboard").tabs("refresh");
            $('#uperAddAdminPhenyxInstaller a').trigger('click');
            getAutocompleteCustomer();			
		}
	});
    
}

function getAutocompleteCustomer() {
	
	
	$.ajax({
		type: 'POST',
		url: AjaxLinkAdminPhenyxInstaller,
		data: {
			action: 'getAutoCompleteCustomer',
			ajax: true
		},
		beforeSend: function(data) {
			clearTimeout(ajax_running_timeout);
		},
		dataType: "json",
		async: true,
		success: function(data) {
			srcCustomer = data;
		}
	})
}


function searchCustomer(ui) {
	ui.autocomplete({
		minLength: 3,
		autoFocus: true,
		classes: {
			"ui-autocomplete": "highlight"
		},
		open: function() {
			var $li = '<li class="autosearch menu-action"><div class="col-lg-6"><button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" id="displayAllCustomer">See all résult</button></div>';
			$li += '<div class="col-lg-6"><button type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" id="createNewCustomer">Create new Customer</button></div></li>';
			$('ul.ui-autocomplete.highlight').prepend($li);
		},
		source: function(request, response) {
			response($.map(srcCustomer, function(value, key) {
				if ((value.customer_code && value.customer_code.toLowerCase().indexOf(request.term.toLowerCase()) != -1) ||
					(value.firstname && value.firstname.toLowerCase().indexOf(request.term.toLowerCase()) != -1) ||
					(value.lastname && value.lastname.toLowerCase().indexOf(request.term.toLowerCase()) != -1) ||
					(value.firstname && value.lastname && (value.firstname.toLowerCase() + ' ' + value.lastname.toLowerCase()).indexOf(request.term.toLowerCase()) != -1) ||
					(value.firstname && value.lastname && (value.lastname.toLowerCase() + ' ' + value.firstname.toLowerCase()).indexOf(request.term.toLowerCase()) != -1)
				) {
					return {
						id_customer: value.id_customer,
						customer_code: value.customer_code,
						firstname: value.firstname,
						lastname: value.lastname,
					}
				} else {
					return null;
				}
				
			}));
		},
		select: function(event, ui) {
			$('#pieceCustomer').val(ui.item.customer_code);
			$('#id_customer').val(ui.item.id_customer);
			$('#customerName').val(ui.item.firstname + ' ' + ui.item.lastname);
			return false;
		},
		_renderMenu: function(ul, item) {
			console.log(ul.find('li').last());
		}
		
	}).data("ui-autocomplete")._renderItem = function(ul, item) {
		var inner_html = '<div>' + item.customer_code + '<span class="customerName"> ' + item.firstname + ' ' + item.lastname + '</span></div>';
		return $('<li class="autosearch ui-menu-item"></li>').data("ui-autocomplete-item", item).append(inner_html).appendTo(ul);
	};
}


function addNewCustomer() {
    
    var website = $('#customer_website').val();
    if(website.length == 0){
		$('#customer_website').addClass('has-error');
        $('#customer_website').focus();
        showErrorMessage(empty_website);
			
    } else {
        $('#website').val(website);
        $('#customer_website').removeClass('has-error');
        $('#newCustomer_submit_button').attr('disabled', 'disabled');
        var formData = new FormData($('form#newCustomer')[0]);
	    $.ajax({
    	   url: AjaxLinkAdminPhenyxInstaller,
           type: "POST",
           data: formData,
		  cache: false,
    	   contentType: false,
    	   processData: false,
            dataType: "json",
            success: function (data) {
        	if (data.success) {
        		showSuccessMessage(data.message);	
                $('#newCustomer').slideUp();
                $('#id_customer').val(data.idCustomer);
			    $('#customerName').val(data.customerName);                
                $('#customer_exist').slideDown();
        	} else {
			    showErrorMessage(data.message);
        	}
        },
    });
        }
}

function addNewInstance() {
    
    var user_ip = $('#user_ip').val();
    var ftp_user = $('#ftp_user').val();
    var ftp_passwd = $('#ftp_passwd').val();
    if(user_ip.length == 0){
		$('#user_ip').addClass('has-error');
        $('#user_ip').focus();
        showErrorMessage(empty_userip);
			
    } else if(ftp_user.length == 0){
		$('#ftp_user').addClass('has-error');
        $('#ftp_user').focus();
        showErrorMessage(empty_ftpuser);
			
    } if(ftp_passwd.length == 0){
		$('#ftp_passwd').addClass('has-error');
        $('#ftp_passwd').focus();
        showErrorMessage(empty_ftppasswd);
			
    } else {
        
        $('#newInstance_submit_button').attr('disabled', 'disabled');
        var formData = new FormData($('form#newInstance')[0]);
	    $.ajax({
    	   url: AjaxLinkAdminPhenyxInstaller,
           type: "POST",
           data: formData,
		  cache: false,
    	   contentType: false,
    	   processData: false,
            dataType: "json",
            success: function (data) {
        	if (data.success) {
        		showSuccessMessage(data.message);	
                closeFormObject('AdminPhenyxInstaller');
                reloadPhenyxInstallerGrid();
        	} else {
			    showErrorMessage(data.message);
        	}
        },
    });
        }
}

function closeInstallerObject() {
	
	
	$('#contentAddAdminPhenyxInstaller').remove();
	$('#uperAddAdminPhenyxInstaller').remove();
    $('#uperAdminPhenyxInstaller a').trigger('click');
    reloadPhenyxInstallerGrid();
	
}