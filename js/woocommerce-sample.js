(function($) {
$.fn.serializeFormJSON = function() {
	var o = {};
	var a = this.serializeArray();
	$(this).find("input[disabled]").each(function(index, element) {
   		a.push( {name: element.name, value: element.value}) ;
	});
	$.each(a, function() {
		if (o[this.name]) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
	return o;
};
})(jQuery);

jQuery(document).ready(function($){
	$('form.sample').on('submit', function(event) {
		var attributes = $('form.variations_form.cart').serializeFormJSON();
		for (k in attributes){
			if ( attributes.hasOwnProperty(k) && ( k.match(/^attribute_.+$/) != null || k == 'variation_id') ){
				console.log(k + ' ' + attributes[k]);
				$(this).append($("<input>").attr("type", "hidden").attr("name", k).val(attributes[k]));
			}
		}
		return true;
		//alert("come la mettiamo?");
		//return false;
	});
});