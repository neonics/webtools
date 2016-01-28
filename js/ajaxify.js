/**
 * Modal Form save.
 *
 * Usage:
 *
 *   $.ajax( ajaxify( form, modal ) )
 *
 */
function ajaxify(form, pe) // AJAX save
{
//			form.find('.btn-primary')[0].value= 'Saving...';//.val('Saving...'); -- bug, no re-layout
	console.log("submitting ", form, "to ", form.attr('action'));

	var data = {};
	for ( var i in d = form.serializeArray() )
	{
//				console.log("FORM", d[i].name, " = ", d[i].value );
		data[d[i].name] = d[i].value;
	}

	var values = data; // alias

	//data = $.extend( data, {id:'purchase-order'} );

	return {
		type:"POST",
		url: form.attr('action'),
		data: $.param( data ),
		error:function(jqXHR,status,error) {
			console.log("ajax form submit response error",status,error,arguments, "form:",form);
			pe.find('.btn-primary').val('Save');
			pe.find('.modal-body').html(
				"<div class='alert alert-danger'>" + error + "<br>"+jqXHR.responseText+"</div>"
			);

		},
		success:function(data,status,jqXHR) {
			pe.find('.modal-body').html( data );

			if (0){
			// set all 'data-saved-value' attributes:
			// this could ahve been done before ajax save...
			// also, is done twice (due to twice post) - possibly refactor this line out!
			for (var i in values) // post data
				if ( i.match(/^col-/) )
					$( 'input[name="'+i+'"]' ).attr( 'data-saved-value', values[i] );
			}

			pe.find('.btn-primary').val('Saved');// theme ani bugs this: [0].style.backgroundColor='green';//.css({background-color:'green'});
		}
	};
}
