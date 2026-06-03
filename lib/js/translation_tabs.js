var expression_translated;

function apiTabTranslate() {
    
    var $rowData = [];
    var data;
    if($('#grid_AdminBackTab').pqGrid("instance")) {
        var grid = $('#grid_AdminBackTab').pqGrid("instance");
        var data = grid.getData();
        $.each(data, function(k, value) {	    
            if(typeof value.translation === 'undefined') {
                var arr = {};
                data = grid.getRowData({
                    rowIndx: k
		        });
                arr['rowIndx'] = k;
                for (var i in data) {
                    if (data.hasOwnProperty(i)) {
                        arr[i] = data[i];
                    }
                }
                $rowData.push(arr);
            }
        });
       		
    } else {
        console.log('no instance')
    }
    
    proceedTabApiTranslate($rowData);
}

function resetTabTranslate() {
    
    var $rowData = [];
    var data;
    if($('#grid_AdminBackTab').pqGrid("instance")) {
        var grid = $('#grid_AdminBackTab').pqGrid("instance");
        var data = grid.getData();
        $.each(data, function(k, value) {	            
             var arr = {};
             data = grid.getRowData({
                 rowIndx: k
		     });
             arr['rowIndx'] = k;
             for (var i in data) {
                 if (data.hasOwnProperty(i)) {
                     arr[i] = data[i];
                 }
             }
             $rowData.push(arr);
        });
       		
    } else {
        console.log('no instance')
    }
    
    proceedTabApiTranslate($rowData);
}

function proceedTabApiTranslate($rowData) {
    
   
    var grid = $('#grid_AdminBackTab').pqGrid("instance");    
	var totalExpressions = $rowData.length;
	var current = 0;
	var row = 0;
	var expressionToTranslate = totalExpressions;
    $('#grid_AdminBackTab .pq-grid-title').html(search_translation+ ' <span id="ace-countdonwn">'+expressionToTranslate+'</span> '+to_remain);
    $.each($rowData, function(index, value) {
        var r = value.expression;

        if (void 0 !== javaFound[r]) {
            grid.deleteRow({ rowIndx: value.rowIndx });
            var c = {
                id_back_tab: value.id_back_tab,
				'plugin': value.plugin,
                class_name: value.class_name,
                name: value.name,
                expression: value.expression,
                translation: javaFound[r]
            };
            grid.addRow({ rowData: c, rowIndx: value.rowIndx });
            expressionToTranslate--;
            $('#ace-countdonwn').html(expressionToTranslate);
            current++;
            if (current == totalExpressions) {
                $('#grid_AdminBackTab .pq-grid-title').html(translation_complete);
            }
        } else {
            $.ajaxq("TranslateProcess", {
                url: AjaxLinkAdminTranslations,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: "getGoogleTranslation",
					file_name:value.class_name, 
                    text: value.name,
                    target: lang_selected,
                    ajax: true
                },
            }).success(function(response) {
                expression_translated = response.translation;
                grid.deleteRow({
                    rowIndx: value.rowIndx
                });
                var rowData = {
                    'id_back_tab': value.id_back_tab,
					'plugin': value.plugin,
                    'class_name': value.class_name,
                    'name': value.name,
                    'expression': value.name,
                    'translation': expression_translated
                };
                grid.addRow({
                    rowData: rowData, rowIndx: value.rowIndx
                });
                expressionToTranslate--;
                $('#ace-countdonwn').html(expressionToTranslate);
                current++;
                if (current == totalExpressions) {
                    $('#grid_AdminBackTab .pq-grid-title').html(translation_complete);
                }
            });
        }
    });
	
}


function getGoogleTranslation(text) {
    var result;
    $.ajax({
		type: "POST",
        url: AjaxLinkAdminTranslations,
        data: {
        	action: "getGoogleTranslation",
            text: text,
            target: lang_selected,
            ajax: true
        },
        async: false,
        dataType: "json",
        success: function success(data) {
            result = data.translation;
               
        }
    });
    
    return result;
}


function saveGridTranslations(type) {
	
	var formData = new FormData($('form#translations_form')[0]);
		
	if($('#grid_AdminBackTab').pqGrid("instance")) {
        var grid = $('#grid_AdminBackTab').pqGrid("instance");
        var data = grid.getData();
        $.each(data, function(k, value) {				
            formData.append(value.id_back_tab, value.translation);
        })	
    } else {
        console.log('no instance')
    }
	
	
	$.ajax({
		url: AjaxLinkAdminTranslations,
		type: 'POST',
		data: formData,
		cache: false,
    	contentType: false,
    	processData: false,
        dataType: "json",
		success: function(data) {
			if (data.success) {
				Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 4000
                })	
			} else {
				Swal.fire({
                            position: 'top-end',
                            icon: 'error',
                            title: data.message,
                            showConfirmButton: false,
                            timer: 4000
                        });
			}
		},
		complete: function complete(data) {
			$('#uperTranslate'+type).remove();
			$('#contentTranslate'+type).remove();
			$('#uperAdminTranslations a').trigger('click');
		}
	});	
}

function saveGridTranslationsAndStay(type) {
    
	var formData = new FormData($('form#translations_form')[0]);
		
	if($('#grid_AdminBackTab').pqGrid("instance")) {
        var grid = $('#grid_AdminBackTab').pqGrid("instance");
        var data = grid.getData();
        $.each(data, function(k, value) {				
            formData.append(value.id_back_tab, value.translation);
        })	
    } else {
        console.log('no instance')
    }
	
	$.ajax({
		url: AjaxLinkAdminTranslations,
		type: 'POST',
		data: formData,
		cache: false,
    	contentType: false,
    	processData: false,
        dataType: "json",
		success: function(data) {
			if (data.success) {
				Swal.fire({
                    position: 'top-end',
                    icon: 'success',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 2000
                })	
			} else {
				Swal.fire({
                    position: 'top-end',
                    icon: 'error',
                    title: data.message,
                    showConfirmButton: false,
                    timer: 3000
                });
			}
		}
	});
	
}

function closeForm(type) {
	
	$('#uperTranslate'+type).remove();
	$('#contentTranslate'+type).remove();
	$('#uperAdminTranslations a').trigger('click');
	
	
}
