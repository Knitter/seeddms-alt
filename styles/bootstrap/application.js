
$(document).ready( function() {
	/* close popovers when clicking somewhere except in the popover or the
	 * remove icon
	 */
	$('html').on('click', function(e) {
		if (typeof $(e.target).data('original-title') == 'undefined' && !$(e.target).parents().is('.popover.in') && !$(e.target).is('.icon-remove')) {
			$('[data-original-title]').popover('hide');
		}
	});

	$('body').on('hidden', '.modal', function () {
		$(this).removeData('modal');
	});

	$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });

	$('#expirationdate, #fromdate, #todate, #createstartdate, #createenddate, #expirationstartdate, #expirationenddate')
		.datepicker()
		.on('changeDate', function(ev){
			$(ev.currentTarget).datepicker('hide');
		});

	$(".chzn-select").chosen();
	$(".chzn-select-deselect").chosen({allow_single_deselect:true});

	/* change the color and length of the bar graph showing the password
	 * strength on each change to the passwod field.
	 */
	$(".pwd").passStrength({
		url: "../op/op.Ajax.php",
		onChange: function(data, target) {
			pwsp = 100*data.score;
			$('#'+target+' div.bar').width(pwsp+'%');
			if(data.ok) {
				$('#'+target+' div.bar').removeClass('bar-danger');
				$('#'+target+' div.bar').addClass('bar-success');
			} else {
				$('#'+target+' div.bar').removeClass('bar-success');
				$('#'+target+' div.bar').addClass('bar-danger');
			}
		}
	});

	/* The typeahead functionality useѕ the rest api */
	$("#searchfield").typeahead({
		minLength: 3,
		source: function(query, process) {
			$.get('../restapi/index.php/search', { query: query, limit: 8, mode: 'typeahead' }, function(data) {
					process(data);
			});
		},
		/* updater is called when the item in the list is clicked. It is
		 * actually provided to update the input field, but here we use
		 * it to set the document location. */
		updater: function (item) {
			document.location = "../op/op.Search.php?query=" + encodeURIComponent(item.substring(1));
			return item;
		},
		/* Set a matcher that allows any returned value */
		matcher : function (item) {
			return true;
		},
		highlighter : function (item) {
			if(item.charAt(0) == 'D')
				return '<i class="icon-file"></i> ' + item.substring(1);
			else if(item.charAt(0) == 'F')
				return '<i class="icon-folder-close-alt"></i> ' + item.substring(1);
			else
				return '<i class="icon-search"></i> ' + item.substring(1);
		}
	});

	/* Document chooser */
	$("[id^=choosedocsearch]").typeahead({
		minLength: 3,
		source: function(query, process) {
//		console.log(this.options);
			$.get('../op/op.Ajax.php', { command: 'searchdocument', query: query, limit: 8 }, function(data) {
					process(data);
			});
		},
		/* updater is called when the item in the list is clicked. It is
		 * actually provided to update the input field where you type, but here
		 * we use it to update a second input field with the doc id. */
		updater: function (item) {
			strarr = item.split("#");
			target = this.$element.data('target');
			$('#'+target).attr('value', strarr[0]);
			return strarr[1];
		},
		/* Set a matcher that allows any returned value */
		matcher : function (item) {
			return true;
		},
		highlighter : function (item) {
			strarr = item.split("#");
			return '<i class="icon-file"></i> ' + strarr[1];
		}
	});

	/* Folder chooser */
	$("[id^=choosefoldersearch]").typeahead({
		minLength: 3,
		source: function(query, process) {
//		console.log(this.options);
			$.get('../op/op.Ajax.php', { command: 'searchfolder', query: query, limit: 8 }, function(data) {
					process(data);
			});
		},
		/* updater is called when the item in the list is clicked. It is
		 * actually provided to update the input field, but here we use
		 * it to set the document location. */
		updater: function (item) {
			strarr = item.split("#");
			//console.log(this.$element.data('target'));
			target = this.$element.data('target');
			$('#'+target).attr('value', strarr[0]);
			return strarr[1];
		},
		/* Set a matcher that allows any returned value */
		matcher : function (item) {
			return true;
		},
		highlighter : function (item) {
			strarr = item.split("#");
			return '<i class="icon-folder-close-alt"></i> ' + strarr[1];
		}
	});

	$('body').on('click', 'button.removedocument', function(ev){
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		attr_formtoken = $(ev.currentTarget).attr('formtoken');
		id = attr_rel;
		$.get('../op/op.Ajax.php',
			{ command: 'deletedocument', id: id, formtoken: attr_formtoken },
			function(data) {
//				console.log(data);
				if(data.success) {
					$('#table-row-document-'+id).hide('slow');
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			},
			'json'
		);
	});

	$('body').on('click', 'button.removefolder', function(ev){
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		attr_formtoken = $(ev.currentTarget).attr('formtoken');
		id = attr_rel;
		$.get('../op/op.Ajax.php',
			{ command: 'deletefolder', id: id, formtoken: attr_formtoken },
			function(data) {
//				console.log(data);
				if(data.success) {
					$('#table-row-folder-'+id).hide('slow');
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			},
			'json'
		);
	});

	$('body').on('click', 'a.addtoclipboard', function(ev){
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		type = attr_rel.substring(0, 1) == 'F' ? 'folder' : 'document';
		id = attr_rel.substring(1);
		$.get('../op/op.Ajax.php',
			{ command: 'addtoclipboard', type: type, id: id },
			function(data) {
				console.log(data);
				if(data.success) {
					$("#main-clipboard").html('Loading').load('../op/op.Ajax.php?command=view&view=mainclipboard')
					$("#menu-clipboard").html('Loading').load('../op/op.Ajax.php?command=view&view=menuclipboard')
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			},
			'json'
		);
	});

	$('body').on('click', 'a.removefromclipboard', function(ev){
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		type = attr_rel.substring(0, 1) == 'F' ? 'folder' : 'document';
		id = attr_rel.substring(1);
		$.get('../op/op.Ajax.php',
			{ command: 'removefromclipboard', type: type, id: id },
			function(data) {
				console.log(data);
				if(data.success) {
					$("#main-clipboard").html('Loading').load('../op/op.Ajax.php?command=view&view=mainclipboard')
					$("#menu-clipboard").html('Loading').load('../op/op.Ajax.php?command=view&view=menuclipboard')
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			},
			'json'
		);
	});

	$('body').on('click', 'a.lock-document-btn', function(ev){
		ev.preventDefault();
		attr_rel = $(ev.currentTarget).attr('rel');
		attr_msg = $(ev.currentTarget).attr('msg');
		id = attr_rel;
		$.get('../op/op.Ajax.php',
			{ command: 'tooglelockdocument', id: id },
			function(data) {
				console.log(data);
				if(data.success) {
					$("#table-row-document-"+id).html('Loading').load('../op/op.Ajax.php?command=view&view=documentlistrow&id='+id)
					noty({
						text: attr_msg,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			},
			'json'
		);
	});
	$('a.sendtestmail').click(function(ev){
		ev.preventDefault();
		$.ajax({url: '../op/op.Ajax.php',
			type: 'GET',
			dataType: "json",
			data: {command: 'testmail'},
			success: function(data) {
				console.log(data);
				noty({
					text: data.msg,
					type: (data.error) ? 'error' : 'success',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 1500,
				});
			}
		}); 
	});

	$('a.movefolder').click(function(ev){
		ev.preventDefault();
		attr_source = $(ev.currentTarget).attr('source');
		attr_dest = $(ev.currentTarget).attr('dest');
		attr_msg = $(ev.currentTarget).attr('msg');
		attr_formtoken = $(ev.currentTarget).attr('formtoken');
		$.get('../op/op.Ajax.php',
			{ command: 'movefolder', folderid: attr_source, targetfolderid: attr_dest, formtoken: attr_formtoken },
			function(data) {
				if(data.success) {
					console.log(data);
					noty({
						text: data.msg,
						type: data.success ? 'success' : 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				}
			},
			'json'
		);
	});

	$('a.movedocument').click(function(ev){
		ev.preventDefault();
		attr_source = $(ev.currentTarget).attr('source');
		attr_dest = $(ev.currentTarget).attr('dest');
		attr_msg = $(ev.currentTarget).attr('msg');
		attr_formtoken = $(ev.currentTarget).attr('formtoken');
		$.get('../op/op.Ajax.php',
			{ command: 'movedocument', docid: attr_source, targetfolderid: attr_dest, formtoken: attr_formtoken },
			function(data) {
				if(data.success) {
					console.log(data);
					noty({
						text: data.msg,
						type: data.success ? 'success' : 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				}
			},
			'json'
		);
	});

	$('.send-missing-translation a').click(function(ev){
//		console.log($(ev.target).parent().children('[name=missing-lang-key]').val());
//		console.log($(ev.target).parent().children('[name=missing-lang-lang]').val());
//		console.log($(ev.target).parent().children('[name=missing-lang-translation]').val());
		$.ajax('../op/op.Ajax.php', {
			type:"POST",
			async:true,
			dataType:"json",
			data: {
				command: 'submittranslation',
				key: $(ev.target).parent().children('[name=missing-lang-key]').val(),
				lang: $(ev.target).parent().children('[name=missing-lang-lang]').val(),
				phrase: $(ev.target).parent().children('[name=missing-lang-translation]').val()
			},
			success: function(data, textStatus) {
//				console.log(data);
				noty({
					text: data.message,
					type: data.success ? 'success' : 'error',
					dismissQueue: true,
					layout: 'topRight',
					theme: 'defaultTheme',
					timeout: 1500,
				});
			}
		});
	});
	
});

$(document).ready( function() {
	$(document).on('change', '.btn-file :file', function() {
		var input = $(this),
		numFiles = input.get(0).files ? input.get(0).files.length : 1,
		label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
		input.trigger('fileselect', [numFiles, label]);
	});

	$('#upload-files').on('fileselect', '.btn-file :file', function(event, numFiles, label) {
		var input = $(this).parents('.input-append').find(':text'),
		log = numFiles > 1 ? numFiles + ' files selected' : label;
	
		if( input.length ) {
			input.val(log);
		} else {
			if( log ) alert(log);
		}
	});
});		

function allowDrop(ev) {
	ev.preventDefault();
	return false;
}

function onDragStartDocument(ev) {
	attr_rel = $(ev.target).attr('rel');
	ev.dataTransfer.setData("id", attr_rel.split("_")[1]);
	ev.dataTransfer.setData("type","document");
}

function onDragStartFolder(ev) {
	attr_rel = $(ev.target).attr('rel');
	ev.dataTransfer.setData("id", attr_rel.split("_")[1]);
	ev.dataTransfer.setData("type","folder");
}

function onDrop(ev) {
	ev.preventDefault();
	ev.stopPropagation();
	attr_rel = $(ev.currentTarget).attr('rel');
	target_type = attr_rel.split("_")[0];
	target_id = attr_rel.split("_")[1];
	source_type = ev.dataTransfer.getData("type");
	source_id = ev.dataTransfer.getData("id");
	if(source_type == 'document') {
		url = "../out/out.MoveDocument.php?documentid="+source_id+"&targetid="+target_id;
		document.location = url;
	} else if(source_type == 'folder') {
		url = "../out/out.MoveFolder.php?folderid="+source_id+"&targetid="+target_id;
		document.location = url;
	}
//	console.log(attr_rel);
//	console.log(ev.dataTransfer.getData("type") + ev.dataTransfer.getData("id"));
}

function onAddClipboard(ev) {
	ev.preventDefault();
	source_type = ev.originalEvent.dataTransfer.getData("type");
	source_id = ev.originalEvent.dataTransfer.getData("id");
	if(source_type == 'document' || source_type == 'folder') {
		$.get('../op/op.Ajax.php',
			{ command: 'addtoclipboard', type: source_type, id: source_id },
			function(data) {
				if(data.success) {
					$("#main-clipboard").html('Loading').load('../op/op.Ajax.php?command=view&view=mainclipboard')
					$("#menu-clipboard").html('Loading').load('../op/op.Ajax.php?command=view&view=menuclipboard')
					noty({
						text: data.message,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			},
			'json'
		);
		//url = "../op/op.AddToClipboard.php?id="+source_id+"&type="+source_type;
		//document.location = url;
	}
}

(function( SeedDMSUpload, $, undefined ) {
	var ajaxurl = "../op/op.Ajax.php";
	var editBtnLabel = "Edit";
	var abortBtnLabel = "Abort";
	var maxFileSize = 100000;
	var maxFileSizeMsg = 'File too large';
	var rowCount=0;

	SeedDMSUpload.setUrl = function(url)  {
		ajaxurl = url;
	}

	SeedDMSUpload.setAbortBtnLabel = function(label)  {
		abortBtnLabel = label;
	}

	SeedDMSUpload.setEditBtnLabel = function(label)  {
		editBtnLabel = label;
	}

	SeedDMSUpload.setMaxFileSize = function(size)  {
		maxFileSize = size;
	}

	SeedDMSUpload.setMaxFileSizeMsg = function(msg)  {
		maxFileSizeMsg = msg;
	}
	
	function sendFileToServer(formData,status) {
		formData.append('command', 'uploaddocument');
		var uploadURL = ajaxurl; //Upload URL
		var extraData ={}; //Extra Data.
		var jqXHR=$.ajax({
			xhr: function() {
			var xhrobj = $.ajaxSettings.xhr();
			if (xhrobj.upload) {
				xhrobj.upload.addEventListener('progress', function(event) {
						var percent = 0;
						var position = event.loaded || event.position;
						var total = event.total;
						if (event.lengthComputable) {
								percent = Math.ceil(position / total * 100);
						}
						//Set progress
						status.setProgress(percent);
					}, false);
				}
				return xhrobj;
			},
			url: uploadURL,
			type: "POST",
			contentType: false,
			dataType:"json",
			processData: false,
			cache: false,
			data: formData,
			success: function(data){
				status.setProgress(100);
//				console.log(data);
				if(data.success) {
					noty({
						text: data.message,
						type: 'success',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 1500,
					});
					status.statusbar.after($('<a href="../out/out.EditDocument.php?documentid=' + data.data + '" class="btn btn-mini btn-primary">' + editBtnLabel + '</a>'));
				} else {
					noty({
						text: data.message,
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 3500,
					});
				}
			}
		}); 

		status.setAbort(jqXHR);
	}

	function createStatusbar(obj) {
		rowCount++;
		var row="odd";
		this.obj = obj;
		if(rowCount %2 ==0) row ="even";
		this.statusbar = $("<div class='statusbar "+row+"'></div>");
		this.filename = $("<div class='filename'></div>").appendTo(this.statusbar);
		this.size = $("<div class='filesize'></div>").appendTo(this.statusbar);
		this.progressBar = $("<div class='progress'><div class='bar bar-success'></div></div>").appendTo(this.statusbar);
		this.abort = $("<div class='btn btn-mini btn-danger'>" + abortBtnLabel + "</div>").appendTo(this.statusbar);
//		$('.statusbar').empty();
		obj.after(this.statusbar);
		this.setFileNameSize = function(name,size) {
			var sizeStr="";
			var sizeKB = size/1024;
			if(parseInt(sizeKB) > 1024) {
				var sizeMB = sizeKB/1024;
				sizeStr = sizeMB.toFixed(2)+" MB";
			} else {
				sizeStr = sizeKB.toFixed(2)+" KB";
			}

			this.filename.html(name);
			this.size.html(sizeStr);
		}
		this.setProgress = function(progress) {       
			var progressBarWidth =progress*this.progressBar.width()/ 100;  
			this.progressBar.find('div').animate({ width: progressBarWidth }, 10).html(progress + "% ");
			if(parseInt(progress) >= 100) {
				this.abort.hide();
			}
		}
		this.setAbort = function(jqxhr) {
			var sb = this.statusbar;
			this.abort.click(function() {
				jqxhr.abort();
				sb.hide();
			});
		}
	}

	SeedDMSUpload.handleFileUpload = function(files,obj) {
		var target = obj.data('target');
		if(target) {
			for (var i = 0; i < files.length; i++) {
				if(files[i].size <= maxFileSize) {
					var fd = new FormData();
					fd.append('folderid', target);
					fd.append('formtoken', obj.data('formtoken'));
					fd.append('userfile', files[i]);

					var status = new createStatusbar(obj);
					status.setFileNameSize(files[i].name,files[i].size);
					sendFileToServer(fd,status);
				} else {
					noty({
						text: maxFileSizeMsg + '<br /><em>' + files[i].name + ' (' + files[i].size + ' Bytes)</em>',
						type: 'error',
						dismissQueue: true,
						layout: 'topRight',
						theme: 'defaultTheme',
						timeout: 5000,
					});
				}
			}
		}
	}
}( window.SeedDMSUpload = window.SeedDMSUpload || {}, jQuery ));

$(document).ready(function() {
	var obj = $("#dragandrophandler");
	obj.on('dragenter', function (e) {
		e.stopPropagation();
		e.preventDefault();
		$(this).css('border', '2px dotted #0B85A1');
	});
	obj.on('dragover', function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
	obj.on('drop', function (e) {
		$(this).css('border', '0px dotted #0B85A1');
		e.preventDefault();
		var files = e.originalEvent.dataTransfer.files;

		//We need to send dropped files to Server
		SeedDMSUpload.handleFileUpload(files,obj);
	});

	var clipboard = $("#main-clipboard");
	clipboard.on('dragenter', function (e) {
		e.stopPropagation();
		e.preventDefault();
		$(this).css('border', '2px dotted #0B85A1');
	});
	clipboard.on('drop', function (e) {
		$(this).css('border', '0px dotted #0B85A1');
		onAddClipboard(e);
	});

	$(document).on('dragenter', function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
	$(document).on('dragover', function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
	$(document).on('drop', function (e) {
		e.stopPropagation();
		e.preventDefault();
	});
 
}); 
