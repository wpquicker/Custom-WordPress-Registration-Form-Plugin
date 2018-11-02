<?php

class Cusregemail {

    function Cusregemail() {
        $this->email = $this->from = $this->fromname = $this->subject = $this->template = $this->data = $this->body = $this->attachment = NULL;
    }

    /*
     * Send WP Email Notification function
     */

    function sendEmail($email = NULL, $from = NULL, $fromname = NULL, $subject = NULL, $template = NULL, $data = NULL, $attachment = NULL) {
        //if values were passed
        if ($email)
            $this->email = $email;
        if ($from)
            $this->from = $from;
        if ($fromname)
            $this->fromname = $fromname;
        if ($subject)
            $this->subject = $subject;
        if ($template)
            $this->template = $template;
        if ($data)
            $this->data = $data;
        if ($attachment)
            $this->attachment = $attachment;

        //default values        
        if (!$this->email)
            $this->email = '';

        if (!$this->from)
            $this->from = get_option("from_emial"); 

        if (!$this->fromname)
            $this->fromname = get_option("from_name");

        if (!$this->subject)
            $this->subject = "An Email From " . get_option("blogname");

        //decode the subject line in case there are apostrophes/etc in it
        $this->subject = html_entity_decode($this->subject, ENT_QUOTES, 'UTF-8');

        if (!$this->template)
            $this->template = "default";
        
        if (!$this->attachment)
            $this->attachment = '';
        

        $this->headers = array("Content-Type: text/html");


        $this->body = file_get_contents(CUSREG_DIR . "/email/" . $this->template . ".html");

        //header and footer
        /* This is handled for all emails via the cusreg_send_html function in paid-memberships-pro now
          if(file_exists(CUSREG_DIR . "/email_header.html"))
          {
          $this->body = file_get_contents(CUSREG_DIR . "/email_header.html") . "\n" . $this->body;
          }
          if(file_exists(CUSREG_DIR . "/email_footer.html"))
          {
          $this->body = $this->body . "\n" . file_get_contents(CUSREG_DIR . "/email_footer.html");
          }
         */

        //swap data
        if (is_string($this->data))
            $data = array("body" => $data);
        if (is_array($this->data)) {
            foreach ($this->data as $key => $value) {
                $this->body = str_replace("!!" . $key . "!!", $value, $this->body);
            }
        }
		//echo $this->body;exit;

        //filters
        /* $this->email = apply_filters("cusreg_email_recipient", $this->email, $this);
          $this->from = apply_filters("cusreg_email_sender", $this->from, $this);
          $this->fromname = apply_filters("cusreg_email_sender_name", $this->fromname, $this);
          $this->subject = apply_filters("cusreg_email_subject", $this->subject, $this);
          $this->template = apply_filters("cusreg_email_template", $this->template, $this);
          $this->body = apply_filters("cusreg_email_body", $this->body, $this);
          $this->headers = apply_filters("cusreg_email_headers", $this->headers, $this); */

        if (wp_mail($this->email, $this->subject, $this->body, $this->headers,$this->attachment)) {
            return true;
        } else {
            return false;
        }
    }

    /*
     * Send Club Owner Welcome Email
     */

    function sendOrgClubOwnerEmail($userdata,$club_code) {
        global $wpdb;

        //$this->email = "nandakumar.arumugam@intergy.com.au"; 
        $this->email = $userdata['email']; 
        $this->subject = "Welcome to NextRep ";
        
        $attachment = array(WP_CONTENT_DIR . '/uploads/club-owner.pdf');
        $this->attachment = $attachment;

        $this->data = array(
            "subject" => $this->subject,
            "first_name" => $userdata['first_name'],
            "club_code" => $club_code,
            "siteurl" => site_url() . "/",
            "clubowner_url" => site_url() . '/nextrep_admin/clubowner/',
        );

        $this->template = "clubowner-template";
        return $this->sendEmail();
    }

    /*
     * Club Owner registration admin notification
     */

    function sendOrgRegAdminEmail($userdata) {
        global $wpdb;

        $organization_type = $wpdb->get_row("SELECT a.id, 
                            a.organization_type 
                            FROM nex_organization_types AS a 
                            where id = " . $userdata['organization_type_id'] . "");

        $this->email = get_option('admin_email'); 
        $this->subject = "Welcome to NextRep ";
        $this->attachment = '';
        
        $this->data = array(
            "subject" => $this->subject,
            "first_name" => $userdata['first_name'],
            "last_name" => $userdata['last_name'],
            "email" => $userdata['email'],
            "club_organization" => $userdata['club_organization_name'],
            "organization_type" => $organization_type->organization_type,
            "siteurl" => site_url(),
        );

        $this->template = "toadmin";
        return $this->sendEmail();
    }

}
