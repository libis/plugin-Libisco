<?php
/**
 * User: NaeemM
 */

if (!defined('LIBCO_DIR')) define('LIBCO_DIR', dirname(__FILE__));
require_once EXHIBIT_PLUGIN_DIR . '/models/Exhibit.php';

class LibcoPlugin extends Omeka_Plugin_AbstractPlugin {

    public $libcoService = null;
    public $recordMpping = null;


    protected $_hooks = array(
        'install',
        'uninstall',
        'after_save_item',
        'after_delete_item',
        'after_save_exhibit',
        'after_delete_exhibit',
//        'before_save_user',  /* GuestUserPlugin should be installed to get this hook. after_save_user hook is not available. */
//        'after_delete_user', /* UserProfilePlugin should be installed to get this hook. */
        'config',
        'config_form'
    );

    protected $_filters = array(

    );


    public function __construct()
    {
        $this->libcoService = new LibcoService();
        $this->recordMpping = new LibcoMappingRecord();

    }

    /**
     * Save / update item
     *
     * @param array $args
     */
    public function hookAfterSaveItem($args)
    {
        $record = $args['record'];
        $recordKey = $this->libcoService->export($record, null);
        $this->recordMpping->addMapping($record->id, strtolower(get_class($record)), $recordKey);

    }

    /**
     * Save / update item
     *
     * @param array $args
     */
    public function hookAfterDeleteItem($args)
    {
        $record = $args['record'];
        $recordKey = $this->recordMpping->getMappingByRecordId($record->id);
        if(!empty($recordKey)){
            $this->libcoService->deleteRecord($recordKey[0]->datastore_key);
            $this->recordMpping->deleteMapping($record->id);
        }

    }

    /**
     * Save / update exhibit
     *
     * @param array $args
     */
    public function hookAfterSaveExhibit($args)
    {
        $record = $args['record'];
        $recordKey = $this->libcoService->export($record, null);
        $this->recordMpping->addMapping($record->id, strtolower(get_class($record)), $recordKey);
    }


    /**
     * Delete exhibit
     *
     * @param array $args
     */
    public function hookAfterDeleteExhibit($args)
    {
        $this->libcoService->deleteRecord($args['record']);
    }

    /**
     * Add / update user
     * @param $args
     */
    public function hookBeforeSaveUser($args)
    {
        //$post = $args['post'];
        //$record = $args['record'];

    }

    /**
     * Delete user
     * @param $args
     */
    public function hookAfterDeleteUser($args)
    {
        $this->libcoService->deleteRecord($args['record']);
        //$user = $args['record'];
        //$userId = $user->id;
    }

    /**
     * Create tables.
     */
    public function hookInstall()
    {
        /* Create table to store omeka-datastore record mapping. */
        $db = get_db();
        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->Libco_Mapping` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
        `record_id` BIGINT UNSIGNED NOT NULL ,
        `record_type` VARCHAR( 255 ) NOT NULL ,
        `datastore_key` VARCHAR( 255 ) NOT NULL
        )
        ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);
    }

    public function hookUpgrade($args)
    {

    }


    /**
     * Drop table / remove configuration options from database.
     */
    public function hookUninstall()
    {
        /* Remove table from database. */
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->Libco_Mapping`";
        $db->query($sql);

        /* Remove options from database. */
        delete_option('libco_server_name');
        delete_option('libco_server_url');
        delete_option('libco_url_path');
        delete_option('libco_server_login_id');
        delete_option('libco_server_login_password');
        delete_option('libco_server_login_token');
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

}