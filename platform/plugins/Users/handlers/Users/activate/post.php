<?php

function Users_activate_post()
{
	Q_Valid::nonce(true);
	
	$email = $mobile = $type = $user = null;
	extract(Users::$cache, EXTR_IF_EXISTS);

	if (isset($_REQUEST['passphrase'])) {
		if (empty($_REQUEST['passphrase'])) {
			throw new Q_Exception("You can't set a blank passphrase.", 'passphrase');
		}

		// Save the pass phrase even if there may be a problem adding an email later.
		// At least the user will be able to log in.
		$user->passphraseHash = Users::hashPassphrase($_REQUEST['passphrase']);
		Q_Response::setNotice("Users/activate/passphrase", "Your pass phrase has been saved.", true);
		// Log the user in, since they were able to set the passphrase
		Users::setLoggedInUser($user); // This also saves the user.

		if (empty($user->passphraseHash)) {
			throw new Q_Exception("Please set a pass phrase on your account", 'passphrase', true);
		}
	}

	if ($type) {
		if ($type == 'email address') {
			$user->setEmailAddress($email->address); // may throw exception	
		} else if ($type == 'mobile number') {
			$user->setMobileNumber($mobile->number); // may throw exception
		}
		// Log the user in, since they have just added an email to their account
		Users::setLoggedInUser($user); // This also saves the user.
		Q_Response::removeNotice('Users/activate/objects');
		Q_Response::setNotice("Users/activate/activated", "Your $type has been activated.", true);
	}
	
	Users::$cache['passphrase_set'] = true;
	Users::$cache['success'] = true;
}
