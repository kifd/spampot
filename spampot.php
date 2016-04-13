<?php
/*
Plugin Name: SpamPot
Version: 0.31
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
		add_action('login_form', array($this, 'replace_fields'));
		add_action('register_form', array($this, 'replace_fields'));
		
		// our css to hide the honeypot
		add_action('login_footer', array($this, 'custom_css'));
	}
	

	public function trap_output() {
		// we're on a page where we do something, so create the real field name
		$data = AUTH_KEY.$_SERVER['REMOTE_ADDR'].date('Ymd');
		$hash = (function_exists('hash')) ? hash('sha256', $data) : md5($data);
		$this->notspam = 'class-'.$hash; // css classes can't start with a number, so make sure we prefix it

		global $action;
		$field = ('login' == $action) ? 'log' : 'user_email';

		if (isset($_POST[$field]) AND '' != $_POST[$field]) {
			// then we (most likely) detected a robot filling in the form fields blindly
			wp_redirect(site_url('wp-login.php?registration=disabled'));
			die(__('If you\'re human and see this message, then please contact the site administrator.'));

		} elseif (isset($_POST[$this->notspam]) AND '' != $_POST[$this->notspam]) {
			// else we switch the fields and let WP go about its business
			$_POST[$field] = $_POST[$this->notspam];
		}

		// and if we're displaying the form again, either for the first time or after a failure, we start buffering now
		ob_start();
	}


	public function replace_fields() {
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
				'tail'	=> 'size="25" tabindex="20"',
			),
		);

		$form = ob_get_contents(); ob_end_clean();
		
		if (! isset($fields[$action])) { echo $form; return; } // in case

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
		
		echo preg_replace('/<p>\s+<label for="'.$fields[$action]['id'].'">[^<]+<br \/>\s+.+<\/label>\s+<\/p>/', $output, $form);
	}

	
	public function custom_css() {
		// on the chance that spambots get smart enough not to fill in form fields that are simply hidden with css, we can easily change this to text-indent etc
		echo '<style type="text/css">p.'.$this->notspam.' { display:none; }</style>';
	}	
}

$SpamPot = new SpamPotPlugin();