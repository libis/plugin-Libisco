<?php
/**
 * User: NaeemM
 */

if (!defined('LIBCO_DIR')) define('LIBCO_DIR', dirname(__FILE__));

class LibcoPlugin extends Omeka_Plugin_AbstractPlugin {

    public $libcoService = null;
    public $recordMpping = null;


    protected $_hooks = array(
        'install',
        'uninstall',
        'config',
        'config_form',
        'define_routes',
        'define_acl'
    );

    protected $_filters = array(
        'public_navigation_main'
    );

    public function hookDefineAcl($args)
    {
        $acl = $args['acl'];
        $acl->addResource('user');
    }

    public function __construct()
    {
        $this->libcoService = new LibcoService();
    }

    /**
     * Create tables.
     */
    public function hookInstall()
    {

    }

    public function hookUpgrade($args)
    {

    }

    /**
     * Drop table / remove configuration options from database.
     */
    public function hookUninstall()
    {
        /* Remove options from database. */
        delete_option('libco_server_name');
        delete_option('libco_server_url');
        delete_option('libco_url_path');
        delete_option('libco_server_proxy');
        delete_option('libco_server_login_token');
    }

    public function hookDefineRoutes($args)
    {
        $router = $args['router'];
        $router->addRoute(
            'libco',
            new Zend_Controller_Router_Route(
                "libco/",
                array('module' => 'libco')
            )
        );

        $router = $args['router'];
        $router->addRoute(
            'eusearch',
            new Zend_Controller_Router_Route(
                "libco/search",
                array('module' => 'libco',
                    'controller' => 'libco',
                    'action' => 'search'
                )
            )
        );

        $router = $args['router'];
        $router->addRoute(
            'eusearchform',
            new Zend_Controller_Router_Route(
                "libco/search",
                array('module' => 'libco',
                    'controller' => 'libco',
                    'action' => 'search-form'
                )
            )
        );
    }

    public function hookConfig($args)
    {
        $post = $args['post'];
        foreach($post as $option=>$value) {
            set_option($option, $value);
        }
    }

    /**
     * Display the plugin config form.
     */
    public function hookConfigForm()
    {
        require dirname(__FILE__) . '/config_form.php';
    }

    public function filterPublicNavigationMain($navArray)
    {
        $navArray['Search Espace'] = array(
            'label'=>__('Search Europeana Space'),
            'uri' => url('libco/libco/search')
        );
        return $navArray;
    }

}