<?php
namespace SimpleSAML\Module\authVHO\Auth\Source;

use SimpleSAML\Module;
use SimpleSAML\Utils\HTTP;

use SimpleSAML_Error_BadRequest;
use SimpleSAML_Error_Exception;
use SimpleSAML_Auth_Source;
use SimpleSAML_Auth_State;

/**
 * Example external authentication source.
 *
 * This class is an authentication source which is designed to
 * hook into an niif:VHO external authentication system.
 *
 *  Add an entry in config/authsources.php referencing your module. E.g.:
 *        'authVHO' => array(
 *            'authVHO:authVHO',
 *        ),
 *
 */
class authVHO extends SimpleSAML_Auth_Source {

    private $config;

    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct($info, $config)
    {
        assert('is_array($info)');
        assert('is_array($config)');

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $this->config = $config;

        // Do any other configuration we need here
    }


    /**
     * Retrieve attributes for the user.
     *
     * @return array|NULL  The user's attributes, or NULL if the user isn't authenticated.
     */
    private function getUser()
    {

        /*
         * In this example we assume that the attributes are
         * stored in the users PHP session, but this could be replaced
         * with anything.
         */

        if (!session_id()) {
            /* session_start not called before. Do it here. */
            session_start();
        }
        if (!isset($_GET['attributes'])) {
            /* The user isn't authenticated. */
            return null;
        }

        $encoded_attributes = $_GET['attributes'];
        $attributes = unserialize(base64_decode(urldecode($encoded_attributes)));

        return $attributes;
    }


    /**
     * Log in using an external authentication helper.
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(&$state)
    {
        assert('is_array($state)');

        $attributes = $this->getUser();
        if ($attributes !== null) {
            /*
             * The user is already authenticated.
             *
             * Add the users attributes to the $state-array, and return control
             * to the authentication process.
             */
            $state['Attributes'] = $attributes;
            return;
        }

        /*
         * The user isn't authenticated. We therefore need to
         * send the user to the login page.
         */

        /*
         * First we add the identifier of this authentication source
         * to the state array, so that we know where to resume.
         */
        $state['authVHO:AuthID'] = $this->authId;


        /*
         * We need to save the $state-array, so that we can resume the
         * login process after authentication.
         *
         * Note the second parameter to the saveState-function. This is a
         * unique identifier for where the state was saved, and must be used
         * again when we retrieve the state.
         *
         * The reason for it is to prevent
         * attacks where the user takes a $state-array saved in one location
         * and restores it in another location, and thus bypasses steps in
         * the authentication process.
         */
        $stateId = SimpleSAML_Auth_State::saveState($state, 'authVHO:AuthID');

        /*
         * Now we generate a URL the user should return to after authentication.
         * We assume that whatever authentication page we send the user to has an
         * option to return the user to a specific page afterwards.
         */
        $returnTo = Module::getModuleURL('authVHO/resume.php', array(
            'State' => $stateId,
        ));

        /*
         * Get the URL of the VHO authentication page.
         *
         * This is in the configuration file.
         */
        
        $authPage = $this->config['vho_login_url'];

        /*
         * The redirect to the authentication page.
         *
         * Note the 'ReturnTo' parameter. This must most likely be replaced with
         * the real name of the parameter for the login page.
         */
        HTTP::redirectTrustedURL($authPage, array(
            'ReturnTo' => $returnTo
        ));

        /*
         * The redirect function never returns, so we never get this far.
         */
        assert('FALSE');
    }


    /**
     * Resume authentication process.
     *
     * This function resumes the authentication process after the user has
     * entered his or her credentials.
     *
     * @param array &$state  The authentication state.
     */
    public static function resume()
    {
        /*
         * First we need to restore the $state-array. We should have the identifier for
         * it in the 'State' request parameter.
         */
        if (!isset($_REQUEST['State'])) {
            throw new Simplesaml_Error_BadRequest('Missing "State" parameter.');
        }

        /*
         * Once again, note the second parameter to the loadState function. This must
         * match the string we used in the saveState-call above.
         */
        // var_dump($_REQUEST['State']);exit;

        $state = SimpleSAML_Auth_State::loadState($_REQUEST['State'], 'authVHO:AuthID');

        /*
         * Now we have the $state-array, and can use it to locate the authentication
         * source.
         */
        $source = SimpleSAML_Auth_Source::getById($state['authVHO:AuthID']);
        if ($source === null) {
            /*
             * The only way this should fail is if we remove or rename the authentication source
             * while the user is at the login page.
             */
            throw new SimpleSAML_Error_Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
        }

        /*
         * Make sure that we haven't switched the source type while the
         * user was at the authentication page. This can only happen if we
         * change config/authsources.php while an user is logging in.
         */
        if (! ($source instanceof self)) {
            throw new SimpleSAML_Error_Exception('Authentication source type changed.');
        }


        /*
         * OK, now we know that our current state is sane. Time to actually log the user in.
         *
         * First we check that the user is acutally logged in, and didn't simply skip the login page.
         */
        $attributes = $source->getUser();
        if ($attributes === null) {
            /*
             * The user isn't authenticated.
             *
             * Here we simply throw an exception, but we could also redirect the user back to the
             * login page.
             */
            throw new SimpleSAML_Error_Exception('User not authenticated after login page.');
        }

        /*
         * So, we have a valid user. Time to resume the authentication process where we
         * paused it in the authenticate()-function above.
         */

        $state['Attributes'] = $attributes;
        SimpleSAML_Auth_Source::completeAuth($state);

        /*
         * The completeAuth-function never returns, so we never get this far.
         */
        assert('FALSE');
    }


    /**
     * This function is called when the user start a logout operation, for example
     * by logging out of a SP that supports single logout.
     *
     * @param array &$state  The logout state array.
     */
    public function logout(&$state)
    {
        assert('is_array($state)');

        if (!session_id()) {
            /* session_start not called before. Do it here. */
            session_start();
        }

        /*
         * In this example we simply remove the 'uid' from the session.
         */
        unset($_SESSION['uid']); //TODO

        /*
         * If we need to do a redirect to a different page, we could do this
         * here, but in this example we don't need to do this.
         */
    }
}
