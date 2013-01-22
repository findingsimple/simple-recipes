var file_frame = new Array();

var count = ( jQuery('.ingredient').length );

//Bind on page load to initial elements
jQuery('.uploader .button').on('click', addMedia );
jQuery('.add_ingredient').on('click', addIngredient );
jQuery('.remove_ingredient').on('click', removeIngredient );

function addMedia( event ){

	var button = jQuery(this);

	var id = button.parent('.uploader').attr('id');;

	event.preventDefault();
	
	// If the media frame already exists, reopen it.
	if ( file_frame[id] ) {
		file_frame[id].open();
		return;
	}
				
	// Create the media frame.
	file_frame[id] = wp.media.frames.file_frame = wp.media({
		title: jQuery( this ).data( 'uploader_title' ),
		button: { text: jQuery( this ).data( 'uploader_button_text' ), },
		multiple: false  // Set to true to allow multiple files to be selected
	});
										
	// When an image is selected, run a callback.
	file_frame[id].on( 'select', function() {
				
		// We set multiple to false so only get one image from the uploader
		attachment = file_frame[id].state().get('selection').first().toJSON();
					
		// Do something with attachment.id and/or attachment.url here
		//button.parent('.uploader').find('#downloadRecipeID').val( attachment.id );
		button.parent('.uploader').find('#downloadRecipe').val( attachment.url );

	});
	
	// Finally, open the modal
	file_frame[id].open();

}	


function addIngredient(){

	//un-bind existing elements
	jQuery('.remove_ingredient').off('click', removeIngredient );
	
	//setup ingredient
	var ingredient = '';
	ingredient += '<li class="ingredient clearfix" id="ingredient-' + count + '">' + "\n";
	ingredient += '<span class="handle ui-icon ui-icon-carat-2-n-s">handle</span>';
	ingredient += '<div><label for="recipe_ingredient_' + count + '" style="display:none;" >Ingredient:</label> <input type="text" id="recipe_ingredient_' + count + '" name="recipe_ingredient[' + count + ']" value="" size="30" tabindex="30" /></div>' + "\n";
	ingredient += '<a href="#" class="remove_ingredient ui-icon ui-icon-circle-minus" title="Remove" >Remove</a>' + "\n";
	ingredient += '</li>';
	
	//append new ingredient field
	jQuery('#ingredients_wrap').append( ingredient );

	//re-bind to updated elements
	jQuery('.remove_ingredient').on('click', removeIngredient);
	
	//increment count
	count++;
	
	return false;

}


function removeIngredient(){
			
	//Count number of sets of input of fields
	var count = jQuery(this).parent().siblings().length;
				
	//Make sure there is at least one set of input fields
	if ( count >= 1 ) {
		
		//un-bind existing elements
		jQuery('.remove_ingredient').off('click', removeIngredient );
		
		//remove ingredient field
		jQuery(this).parent().remove();
		
		//re-bind to updated elements
		jQuery('.remove_ingredient').on('click', removeIngredient );				

	}
	
	return false;
	
}

jQuery( ".wrap #ingredients_wrap" ).sortable({ handle: ".handle" });
//jQuery( ".wrap #ingredients_wrap" ).disableSelection();