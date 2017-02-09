require([
    'jquery',
    'mage/validation'
], function($){

    var dataForm = $('#request_tutor');
    var ignore = null;

    dataForm.mage('validation', {
        ignore: ignore ? ':hidden:not(' + ignore + ')' : ':hidden'
    }).find('input:text').attr('autocomplete', 'off');

    $('.submitconfig').click( function() { //can be replaced with any event
        dataForm.validation('isValid'); //validates form and returns boolean
        if(!(dataForm.validation('isValid')))
        {
        	return false;
        } 
         
    });
}); 
 