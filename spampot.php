<?php
/*
Plugin Name: SpamPot
Version: 0.34
Plugin URI: http://drakard.com/
Description: Adds a honeypot form field on the registration and login pages to trap spammers.
Author: Keith Drakard
Author URI: http://drakard.com/
*/


class SpamPotPlugin {
	
	/* INIT *************************************************************************************/
	public function __construct() {
		$this->notspam = 'not-used-on-this-page';

		// switch our notspam field for the honeypot
		add_action('login_form_register', array($this, 'trap_output'), 1);
		add_action('login_form_login', array($this, 'trap_output'), 1);
		
		// add the honeypot field to the forms
		add_action('login_head', array($this, 'spampot_trap_start'), 191);
		add_action('login_footer', array($this, 'spampot_trap_end'), 191);
		
		// our css to hide the honeypot
		add_action('login_footer', array($this, 'custom_css'));
	}
	

	public function trap_output() {
		// we're on a page where we do something, so create the real field name
		$data = AUTH_KEY.$_SERVER['REMOTE_ADDR'].date('Ymd');
		$hash = (function_exists('hash')) ? hash('sha256', $data) : md5($data);
		$this->notspam = 'class-'.$hash; // css classes can't start with a number, so make sure we prefix it

		// login or registration?
		global $action;

		// first check that we have a honeypot field inserted ok
		if (isset($_POST['error-'.$this->notspam])) {
			// then we failed to do anything, so notify the admin and bail now rather than break the form

			// TODO: notice on the dashboard instead?
			$to = get_option('admin_email'); if (! $to) return; // no admin email, can't notify them
			$website = get_option('blogname', __('your website', 'SpamPot'));
			$subject = __('SpamPot Plugin Failed', 'SpamPot');
			$message = __("Hi,\n\nthis is an automated notice to let you know that the SpamPot plugin you installed on {$website} is unable to insert the honeypot field into the {$action} form.\n\nPlease raise a support thread at https://wordpress.org/support/plugin/spampot saying which version of WP you use, and any plugins that alter that form.\n\nregards,\nkeith_wp", 'SpamPot');
			wp_mail($to, $subject, $message);

			return;
		}

		$field = ('login' == $action) ? 'log' : 'user_email';

		if (isset($_POST[$field]) AND '' != $_POST[$field]) {
			// then we (most likely) detected a robot filling in the form fields blindly
			wp_redirect(site_url('wp-login.php?registration=disabled'));
			die(__('If you\'re human and see this message, then please contact the site administrator.', 'SpamPot'));

		} elseif (isset($_POST[$this->notspam]) AND '' != $_POST[$this->notspam]) {
			// else we switch the fields and let WP go about its business
			$_POST[$field] = $_POST[$this->notspam];
		}

	}


	public function spampot_trap_start() {
		ob_start(array($this, 'replace_fields'));
	}

	public function replace_fields($body) {
		global $action, $user_email;

		$fields = array(
			'login' => array(
				'id'	=>	'user_login',
				'text'	=> __('Username or Email'),
				'type'	=> 'text',
				'name'	=> 'log',
				'aria'	=> ' aria-describedby="login_error"',
				'value'	=> '',
				'tail'	=> 'size="20"',
			),
			'register' => array(
				'id'	=>	'user_email',
				'text'	=> __('Email'),
				'type'	=> 'email',
				'name'	=> 'user_email',
				'aria'	=> '',
				'value'	=> esc_attr(stripslashes($user_email)),
				'tail'	=> 'size="25"',
			),
		);

		
		if (! isset($fields[$action])) { return $body; } // in case

		$crtab = chr(10).chr(9).chr(9);

		// don't fill this one out
		$output = '<p class="'.$this->notspam.'">'.$crtab.'<label for="'.$fields[$action]['id'].'">'.$fields[$action]['text'].'<br />'.$crtab
				. '<input type="'.$fields[$action]['type'].'" name="'.$fields[$action]['name'].'" id="'.$fields[$action]['id'].'"'.$fields[$action]['aria']
				.' class="input" value="'.$fields[$action]['value'].'" '.$fields[$action]['tail'].' />'
				. '</label>'.chr(10).chr(9).'</p>'.chr(10).chr(9);

		// the real email field now
		$output.= '<p>'.$crtab.'<label for="'.$this->notspam.'">'.$fields[$action]['text'].'<br />'.$crtab
				. '<input type="'.$fields[$action]['type'].'" name="'.$this->notspam.'" id="'.$this->notspam.'" class="input" value="" '.$fields[$action]['tail'].' />'
				. '</label>'.chr(10).chr(9).'</p>'.chr(10).chr(9);
		
		// hopefully a more bulletproof regex
		$changed = preg_replace('|<p>\s*<label for="'.$fields[$action]['id'].'">.+?</label>\s*</p>|s', $output, $body);

		// if we haven't inserted the honeypot, then don't break the form
		if ($changed == $body) {
			$changed = preg_replace('|</form>|', '<input type="hidden" name="error-'.$this->notspam.'" value="'.__('Could not insert honeypot field', 'SpamPot').'"></form>', $body);
		}

		return $changed;
	}

	public function spampot_trap_end() {
		ob_get_flush();
	}

	
	public function custom_css() {
		// on the chance that spambots get smart enough not to fill in form fields that are simply hidden with css, we can easily change this to text-indent etc
		echo '<style type="text/css">p.'.$this->notspam.' { display:none; }</style>';
	}	
}

$SpamPot = new SpamPotPlugin();