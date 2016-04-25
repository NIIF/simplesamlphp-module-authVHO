<?php

/**
 * A simple processing filter for testing that redirection works as it should.
 *
 */
class sspmod_authVHO_Auth_Process_RedirectTest extends SimpleSAML_Auth_ProcessingFilter {


	/**
	 * Initialize processing of the redirect test.
	 *
	 * @param array &$state  The state we should update.
	 */
	public function process(&$state) {
		assert('is_array($state)');
		assert('array_key_exists("Attributes", $state)');

		// To check whether the state is saved correctly
		$state['Attributes']['RedirectTest1'] = array('OK');

		// Save state and redirect
		$id = SimpleSAML_Auth_State::saveState($state, 'authVHO:redirectfilter-test');
		$url = SimpleSAML_Module::getModuleURL('authVHO/redirecttest.php');
		\SimpleSAML\Utils\HTTP::redirectTrustedURL($url, array('StateId' => $id));
	}

}
