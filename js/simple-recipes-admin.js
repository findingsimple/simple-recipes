var count = ( jQuery('.ingredient').length );

jQuery('.add_ingredient').on('click', addIngredient );
jQuery('.remove_ingredient').on('click', removeIngredient );

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