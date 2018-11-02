
/*
 * Club registration client side validation
 */
jQuery(document).on("click", "#club_registration_form #cus_submit", function (e) {   
    jQuery.validator.addMethod("lettersonly_firstname", function (value, element) {
        return this.optional(element) || /^[a-z\s]+$/i.test(value);
    }, "Please enter valid first name.");
    jQuery.validator.addMethod("lettersonly_lastname", function (value, element) {
        return this.optional(element) || /^[a-z\s]+$/i.test(value);
    }, "Please enter valid last name.");
    jQuery.validator.addMethod("valid_org", function (value, element) {
        return this.optional(element) || /^[A-Z0-9\-' &]+$/i.test(value);
    }, "Please enter valid club/organization name.");

    jQuery.validator.addMethod("validpassword", function (value, element) {
        return this.optional(element) || /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{3,16}$/i.test(value);
    }, "Password should contain of one number, one upper case letter and one small case letter.");

    jQuery.validator.addMethod("uniqueEmailAdd", function (value, element) {

       var feedback = jQuery.ajax({
            url: ajax_object.ajaxurl,
            type: 'POST',
            async: false,
            data: {'email': value, action: 'uniqueEmail'},            
        }).responseText;
        
        if(feedback == ''){
            return false;
        }
        else{
            return true;
        }        
        
    }, "Email address already exists.");


    jQuery("#club_registration_form").validate({
        rules: {
            "first_name": {
                required: true
                //lettersonly_firstname: true //Hide by Murugesh
            },
            "last_name": {
                required: true
                //lettersonly_lastname: true //Hide by Murugesh
            },
            "email": {
                required: true,
                email: true,
                //uniqueEmailAdd: true
            },
            "password": {
                required: true,
                validpassword: true
            },
            "cpassword": {
                required: true,
                equalTo: "#password"
            },
            "club_organization_name": {
                required: true,
                valid_org: true
            },
            "organization_type_id": {
                required: true
            },
            "agree_register": {
                required: true
            },
        },
        messages: {
            "first_name": {
                required: "Please enter first name."
            },
            "last_name": {
                required: "Please enter last name."
            },
            "email": {
                required: "Please enter email address."
            },
            "password": {
                required: "Please enter password."
            },
            "cpassword": {
                required: "Please enter confirm password.",
				equalTo: "Confirm Password should be same as given password."
            },
            "club_organization_name": {
                required: "Please enter club/organization name."
            },
            "organization_type_id": {
                required: "Please select organization type."
            },
            "agree_register": {
                required: "Please select agree to register."
            },
        },
        onkeyup: false,
        submitHandler: function (form) {
            document.club_registration_form.submit();

        }
    });
});