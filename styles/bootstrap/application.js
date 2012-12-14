
$(document).ready( function() {
$('body').on('touchstart.dropdown', '.dropdown-menu', function (e) { e.stopPropagation(); });
$('#expirationdate, #fromdate, #todate')
	.datepicker()
	.on('changeDate', function(ev){
		$('#expirationdate, #fromdate, #todate').datepicker('hide');
	});

	$(".chzn-select").chosen();
	$(".chzn-select-deselect").chosen({allow_single_deselect:true});

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
});
