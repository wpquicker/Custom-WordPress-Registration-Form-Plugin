<?php

/*
  Plugin Name: Custom Registration
  Plugin URI:
  Description: Custom registration form with server and client side validation.
  Version: 1.0
  Author: Dhanasekaran
  Author URI:
 */

define("CUSREG_DIR", dirname(__FILE__));
require_once(CUSREG_DIR . "/includes/lib/bcrypt.php");
require_once(CUSREG_DIR . "/includes/message-defined.php");

/*
 * nclude JS file and define ajax URL.
 */

function cusreg_init() {
    wp_enqueue_script('cusreg_js', plugins_url('js/custom-registration.js', __FILE__), array('jquery'));   
}

add_action("init", "cusreg_init");

if (!function_exists('load_my_cus_styles')) {
    function load_my_cus_styles() {        
            wp_register_style('cus_reg', plugins_url('css/custom-styles.css', __FILE__));     
            wp_enqueue_style('cus_reg');     
            
            // wp_register_style('cus_reg', plugins_url() . '/css/custom-styles.css');     
            // wp_enqueue_style('cus_reg');      
    }
}
add_action('init', 'load_my_cus_styles');

/*
 * Define registration HTML form
 */

function registration_form($first_name, $last_name, $email, $password, $cpassword, $club_organization_name, $organization_type_id, $agree_register) {

    global $wpdb;
    $organization_type = $wpdb->get_results("SELECT a.id, 
        a.organization_type 
            FROM nex_organization_types AS a 
            where status = 1");

    $org_type_data = getOrganizationType($organization_type, $organization_type_id);

    echo '
    <div class="join-now member-access-form-wrap">
        <h1>Thinking about joining?</h1>
        <h2>Join today!</h2>
    </div>
    <div class="um um-register um-err um-12471 custom-register"> 
    <div class="um-form member-access-form-cont">
    <form action="' . $_SERVER['REQUEST_URI'] . '" method="post" name="club_registration" id="club_registration_form" class="form-custom">
    <div class="um-row _um_row_1" style="margin: 0 0 30px 0;"> 

            <div class="form-group">
                <label class="control-label" for="First Name">First Name <span>*</span></label>
                <input class="form-control" type="text" name="first_name" id="first_name" value="' . ( isset($_POST['first_name']) ? $first_name : null ) . '" maxlength="50">
            </div> 

            <div class="form-group">
                <label class="control-label" for="Last Name">Last Name <span>*</span></label>
                <input class="form-control" type="text" name="last_name" id="last_name" value="' . ( isset($_POST['last_name']) ? $last_name : null ) . '" maxlength="50">
            </div>

            <div class="form-group">
                <label class="control-label" for="email">Email <span>*</span></label>
                <input class="form-control" type="text" name="email" id="email" value="' . ( isset($_POST['email']) ? $email : null ) . '" maxlength="100">
            </div>

            <div class="form-group">
                <label class="control-label" for="Password">Password <span>*</span></label>
                <input class="form-control" type="password" name="password"  id="password" value="' . ( isset($_POST['password']) ? $password : null ) . '" maxlength="255">
            </div>

            <div class="form-group">
                <label class="control-label" for="Confirm Password">Confirm Password <span>*</span></label>
                <input class="form-control" type="password" name="cpassword" value="' . ( isset($_POST['cpassword']) ? $cpassword : null ) . '" maxlength="255">
            </div>

            <div class="form-group">
                <label class="control-label" for="Organization type">Organization type <span>*</span></label>
                ' . $org_type_data . '
            </div>

            <div class="form-group">
                <label class="control-label" for="Club / Organization name">Club / Organization name <span>*</span></label>
                <input class="form-control" type="text" name="club_organization_name" id="club_organization_name" value="' . ( isset($_POST['club_organization_name']) ? $club_organization_name : null ) . '" maxlength="100">
            </div>

            <div class="form-group group-checkbox">  
                <div class="checkbox">
                <label>
                    <input type="checkbox" name="agree_register" id="agree_register" value="1" ' . ( isset($_POST['agree_register']) ? 'checked=checked' : '' ) . '> <span>I agree to register</span
                    </label>
                    
                </div> 
            </div>     
            <div class="form-group">
                <div class="btn-col"> 
                    <input class="btn-primary" type="submit" name="submit" value="Submit" id="cus_submit">
                </div>
                <div class="btn-col-last">
                    <a href="' . get_bloginfo('url') . '/login" class="btn-primary">Go to Login page</a>
                </div> 
            </div>
    </div>

</div>     
</form>
</div>
</div>
    ';
}

/*
 * Check validation error and complete the registration process.
 * Used Bcrypt library for the password hashing.
 * We can redirect any URL after DB insert.
 */

function complete_registration() {
    global $wpdb, $reg_errors, $first_name, $last_name, $email, $password, $cpassword, $club_organization_name, $organization_type_id, $agree_register;
    if (1 > count($reg_errors->get_error_messages())) {

        $bcrypt = new Bcrypt(15);
        $hashPassword = $bcrypt->hash($password);


        $userdata = array(
            'user_type' => 2,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $hashPassword,
            'club_organization_name' => $club_organization_name,
            'organization_type_id' => $organization_type_id,
            'status' => 1,
            'created_by' => 1,
            'created_date' => date('Y-m-d H:s:i'),
        );
        $wpdb->insert('nex_users', $userdata);
        $userId = $wpdb->insert_id;
        $club_code = generateRandomString();

        $wpdb->query("UPDATE nex_users SET club_code = '" . $club_code . "' where id = " . $userId . "");


        require_once(CUSREG_DIR . "/classes/class.cusregemail.php");
        $myemail = new Cusregemail();
        $myemail->sendOrgClubOwnerEmail($userdata, $club_code);
        $myemail->sendOrgRegAdminEmail($userdata);

        //$user = wp_insert_user($userdata);
        $url = site_url() . '/club-registration-success/';
        wp_redirect($url);
        exit;
    }
}

/*
 * Get the from input and process the validation.
 * complete_registration to create the user only when no WP_error is found
 */

function custom_registration_function() {
    if (isset($_POST['submit'])) {
        registration_validation(
                $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['password'], $_POST['cpassword'], $_POST['club_organization_name'], $_POST['organization_type_id'], $_POST['agree_register']
        );

        // sanitize user form input
        global $first_name, $last_name, $email, $password, $cpassword, $club_organization_name, $organization_type_id, $agree_register;
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = esc_attr($_POST['password']);
        $cpassword = esc_attr($_POST['cpassword']);
        $club_organization_name = sanitize_text_field($_POST['club_organization_name']);
        $organization_type_id = $_POST['organization_type_id'];
        $nickname = sanitize_text_field($_POST['agree_register']);


        // call @function complete_registration to create the user
        // only when no WP_error is found
        complete_registration(
                $first_name, $last_name, $email, $password, $cpassword, $club_organization_name, $organization_type_id, $agree_register
        );
    }

    registration_form(
            $first_name, $last_name, $email, $password, $cpassword, $club_organization_name, $organization_type_id, $agree_register
    );
}

/*
 * Register a new shortcode: [cr_custom_registration]
 * We can use short code in PAGE, POST, WIDGET & PHP script do_shortcode
 */
add_shortcode('cr_custom_registration', 'custom_registration_shortcode');

function custom_registration_shortcode() {
    ob_start();
    custom_registration_function();
    return ob_get_clean();
}

/*
 * We have check the server side validation here.
 * Consolidate the  WP_Error and display in the top of the form.
 */

function registration_validation($first_name, $last_name, $email, $password, $cpassword, $club_organization_name, $organization_type_id, $agree_register) {
    global $reg_errors;
    $reg_errors = new WP_Error;

    if (empty($first_name)) {
        $reg_errors->add('field', ERR_FIRST_NAME);
    } else if (!empty($first_name)) {
        if (!ValidCommonNameCheck($first_name)) {
            $reg_errors->add('field', ERR_VALID_FIRST_NAME);
        }
    }

    if (empty($last_name)) {
        $reg_errors->add('field', ERR_LAST_NAME);
    } else if (!empty($last_name)) {
        if (!ValidCommonNameCheck($last_name)) {
            $reg_errors->add('field', ERR_VALID_LAST_NAME);
        }
    }

    if (empty($email)) {
        $reg_errors->add('field', ERR_EMAIL);
    }

    if (!empty($email) && !is_email($email)) {
        $reg_errors->add('email_invalid', ERR_VALID_EMAIL);
    }

    if (!empty($email)) {
        if (!emailAlreayExists($email)) {
            $reg_errors->add('email_invalid', ERR_ALREADY_EMAIL);
        }
    }
    

    if (empty($password)) {
        $reg_errors->add('field', 'Please enter password.');
    } else if (!empty($password)) {
        if (!passwordCheck($password)) {
            $reg_errors->add('field', ERR_VALID_PASSWORD);
        }
    }

    if (empty($cpassword) && !empty($password)) {
        $reg_errors->add('field', ERR_CPASSWORD);
    } else if (!empty($cpassword)) {
        if (!confirmpasswordCheck($cpassword, $password)) {
            $reg_errors->add('field', ERR_PASSWORD_MISSMATCH);
        }
    }

    if (empty($club_organization_name)) {
        $reg_errors->add('field', ERR_ORG);
    } else if (!empty($club_organization_name)) {
        if (!validCompanyNameCheck($club_organization_name)) {
            $reg_errors->add('field', ERR_VALID_ORG);
        }
    }
    if (!empty($club_organization_name)) {
        if (!OrganizationAlreayExists($club_organization_name)) {
            $reg_errors->add('field', ERR_ALREADY_ORG);
        }
    }

    if (empty($organization_type_id)) {
        $reg_errors->add('field', ERR_ORG_TYPE);
    }


    if (empty($agree_register)) {
        $reg_errors->add('field', ERR_AGREE);
    }


    if (is_wp_error($reg_errors)) {
        echo '<div class="col-md-offset-3 col-md-6"><div class="fusion-alert alert error alert-dismissable alert-danger alert-shadow text-left"><button type="button" class="close toggle-alert" data-dismiss="alert" aria-hidden="true">×</button>';
        foreach ($reg_errors->get_error_messages() as $error) {
            echo '<p><span class="alert-icon"><i class="fa fa-lg fa-exclamation-triangle"></i></span>' . $error . '</p>';
        }
        echo '</div></div>';
    }
}

/*
 * Vlaidate Name special character
 */

function ValidCommonNameCheck($str) {
    if (preg_match('/^[A-Z\' ]+$/i', $str)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/*
 * Vlaidate Organization special character (Allow Numbers)
 */

function validCompanyNameCheck($str) {
    if (preg_match("/^[A-Z0-9\-' &]+$/i", $str)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/*
 * Password should contain of one number, one upper case letter and one small case letter.
 */

function passwordCheck($str) {
    if (preg_match('#[0-9]#', $str) && preg_match('#[a-z]#', $str) && preg_match('#[A-Z]#', $str)) {
        return TRUE;
    }
    return FALSE;
}

/*
 * Check Confirm Password matching
 */

function confirmpasswordCheck($cpassword, $password) {
    if (strcmp($password, $cpassword) !== 0) {
        return FALSE;
    }
    return TRUE;
}

/*
 * Get thr Organization type from Database and build the Drop Down
 */

function getOrganizationType($organization_type, $organization_type_id) {
    $measurement = "<Select name='organization_type_id' id='organization_type_id'>";
    $measurement .= "<option value=''>Select organization type</option>";
    if (count($organization_type) > 0) {
        for ($i = 0; $i < count($organization_type); $i++) {
            $sel = '';
            if ($organization_type_id == $organization_type[$i]->id) {
                $sel = 'selected=selected';
            }
            $measurement .= "<option value='" . $organization_type[$i]->id . "' " . $sel . ">" . $organization_type[$i]->organization_type . "</option>";
        }
    }
    return $measurement .= "</Select>";
}

/*
 * Unique Email Address check
 */

function emailAlreayExists($email) {
    global $wpdb;
    $userData = $wpdb->get_results("SELECT a.id, 
        a.email 
            FROM nex_users AS a 
            where a.email = '" . $email . "' and a.user_type = 2 and a.is_deleted = 0");


    if (count($userData) > 0) {
        return FALSE;
    } else {
        return TRUE;
    }
}
function  OrganizationAlreayExists($club_organization_name) {
    global $wpdb;
    $userData = $wpdb->get_results("SELECT a.id, 
        a.club_organization_name 
            FROM nex_users AS a 
            where a.club_organization_name = '" . $club_organization_name . "' and a.user_type = 2 and a.is_deleted = 0");


    if (count($userData) > 0) {
        return FALSE;
    } else {
        return TRUE;
    }
}

/*
 * For client side validation, we checking the Email already register. (Ajax method)
 */
add_action('wp_ajax_uniqueEmail', 'uniqueEmail');
add_action('wp_ajax_nopriv_uniqueEmail', 'uniqueEmail');

function uniqueEmail() {
    if ($_POST['email'] != '') {
        echo emailAlreayExists($_POST['email']);
    } else {
        return FALSE;
    }
    die();
}

/*
 * Club Code
 */

function generateRandomString($length = 8, $possible = null) {

    global $wpdb;
    while (empty($password)) {
        // define possible characters
        if (!$possible)
            $possible = "0123456789bcdfghjkmnpqrstvwxyz";
        // set up a counter
        $i = 0;
        // add random characters to $password until $length is reached
        while ($i < $length) {
            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            // we don't want this character if it's already in the password
            if (!strstr($password, $char)) {
                $password .= $char;
                $i++;
            }
        }

        $check = $wpdb->get_var("SELECT club_code FROM nex_users WHERE club_code = '$password' LIMIT 1");

        if ($check) {
            $code = NULL;
        }
    }

    return $password;
}

?>