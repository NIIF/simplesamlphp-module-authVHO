<?php
namespace SimpleSAML\Module\authVHO\Auth\Process;

use SimpleSAML\Module;
use SimpleSAML_Auth_ProcessingFilter;
use SimpleSAML_Auth_State;
use SimpleSAML\Utils;

/**
 * A simple processing filter for testing that redirection works as it should.
 *
 */
class RedirectTest extends SimpleSAML_Auth_ProcessingFilter
{
    /**
     * Initialize processing of the redirect test.
     *
     * @param array &$state  The state we should update.
     */
    public function process(&$state)
    {
        assert('is_array($state)');
        assert('array_key_exists("Attributes", $state)');

        // To check whether the state is saved correctly
        $state['Attributes']['RedirectTest1'] = array('OK');

        // Save state and redirect
        $id = SimpleSAML_Auth_State::saveState($state, 'authVHO:redirectfilter-test');
        $url = Module::getModuleURL('authVHO/redirecttest.php');
        HTTP::redirectTrustedURL($url, array('StateId' => $id));
    }
}
