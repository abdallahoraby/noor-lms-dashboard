<?php

if (!defined('ABSPATH')) {
    exit;
}

class ACOOFMF_Api
{

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $instance = null;

    /**
     * The version number.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $version;
    /**
     * The token.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $token;

    public function __construct()
    {
        $this->token = ACOOFM_TOKEN;

        add_action( 'rest_api_init', array($this, 'api_register'));
    }

    /**
     * Apis
     * @since 1.0.0
     */

    public function api_register()
    {
        $this->add_route('/commonSettings/', 'commonSettings' );
        $this->add_route('/updateCommonSettings/', 'updateCommonSettings', 'POST' );
        $this->add_route('/serviceConnect/', 'serviceConnect', 'POST' );
        $this->add_route('/serviceSave/', 'serviceSave', 'POST' );
        $this->add_route('/settingsSave/', 'settingsSave', 'POST' );
        $this->add_route('/settingsReset/', 'settingsReset', 'POST' );
        $this->add_route('/uploadCredentials/', 'uploadCredentials', 'POST' );
    }

    /**
     * Function to add route
     */
    private function add_route( $slug, $callBack, $method = 'GET' ) {
		register_rest_route(
			$this->token . '/v1',
			$slug,
			array(
				'methods'             => $method,
				'callback'            => array( $this, $callBack ),
				'permission_callback' => array( $this, 'getPermission' ),
			) );
	}


    /**
     * Get all settings
     * @since 1.0.0
     */

    public function commonSettings()
    {
        // Run API call to retrieve server data
        $data = array(
            'dashboard' => [
                'offloaded' => acoofm_get_media_count('offloaded'),
                'version' => 'v' . ACOOFM_VERSION,
            ],
            'configure' => [
                'service' => acoofm_get_option('service', []),
                'credentials' => acoofm_get_option('credentials', []),
            ],
            'settings' => acoofm_get_option('settings', []),
        );
        return new WP_REST_Response($data, 200);
        // return new WP_REST_Response('Error Fetching Data', 503);
    }

    /**
     * Update Default Settings
     * @since 1.0.0
     */
    public function updateCommonSettings($request)
    {
        $settings = $request->get_param('settings');
        $configure = $request->get_param('configure');
        if (
            isset($configure) && !empty($configure) &&
            isset($settings) && !empty($settings)
        ) {
            if (acoofm_update_option('settings', $settings)) {
                if (isset($configure['service']) && !empty($configure['service'])) {
                    acoofm_update_option('service', $configure['service']);
                }
                if ($configure['credentials'] && !empty($configure['credentials'])) {
                    acoofm_update_option('credentials', $configure['credentials']);
                }
            }
        }
        return new WP_REST_Response(__('Configuration Saved', 'offload-media-cloud-storage'), 200);
    }

    /**
     * Service Connect and verify credentials
     * @since 1.0.0
     */
    public function serviceConnect($request)
    {
        $service = $request->get_param('service');
        $credentials = $request->get_param('credentials');

        if (
            isset($service) && !empty($service) &&
            isset($credentials) && !empty($credentials)
        ) {
            if (isset($service['slug']) && !empty($service['slug'])) {
                switch ($service['slug']) {
                    case 's3':
                        $acoofmS3 = new ACOOFMF_S3;
                        if (
                            isset($credentials['region']) && !empty($credentials['region']) &&
                            isset($credentials['access_key']) && !empty($credentials['access_key']) &&
                            isset($credentials['secret_key']) && !empty($credentials['secret_key']) &&
                            isset($credentials['bucket_name']) && !empty($credentials['bucket_name'])
                        ) {
                            $result = $acoofmS3->verify($credentials['access_key'], $credentials['secret_key'], $credentials['region'], $credentials['bucket_name']);
                            return new WP_REST_Response(array('message' => $result['message'], 'code' => $result['code']));
                        } else {
                            return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405));
                        }
                        break;
                    case 'google':
                        $acoofmGoogle = new ACOOFMF_GOOGLE;
                        if (
                            isset($credentials['config_json']) && !empty($credentials['config_json']) &&
                            isset($credentials['google_bucket_name']) && !empty($credentials['google_bucket_name'])
                        ) {
                            $result = $acoofmGoogle->verify($credentials['config_json'], $credentials['google_bucket_name']);
                            return new WP_REST_Response(array('message' => $result['message'], 'code' => $result['code']));
                        } else {
                            return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405));
                        }
                        break;
                    case 'ocean':
                        $acoofmOcean = new ACOOFMF_DIGITALOCEAN;
                        if (
                            isset($credentials['ocean_region']) && !empty($credentials['ocean_region']) &&
                            isset($credentials['ocean_access_key']) && !empty($credentials['ocean_access_key']) &&
                            isset($credentials['ocean_secret_key']) && !empty($credentials['ocean_secret_key']) &&
                            isset($credentials['ocean_bucket_name']) && !empty($credentials['ocean_bucket_name'])
                        ) {
                            $result = $acoofmOcean->verify($credentials['ocean_access_key'], $credentials['ocean_secret_key'], $credentials['ocean_region'], $credentials['ocean_bucket_name']);
                            return new WP_REST_Response(array('message' => $result['message'], 'code' => $result['code']));
                        } else {
                            return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405));
                        }
                        break;
                    default:
                        return new WP_REST_Response(array('message' => __('Invalid Service Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405));
                }
            } else {
                return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405));
            }
        } else {
            return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405));
        }
    }

    /**
     * Service Credentials and service save
     * @since 1.0.0
     */
    public function serviceSave($request)
    {
        $service = $request->get_param('service');
        $credentials = $request->get_param('credentials');
        $settings = $request->get_param('settings');

        $current_service = acoofm_get_service('slug');
        if (
            isset($service) && !empty($service) &&
            isset($credentials) && !empty($credentials) &&
            isset($settings) && !empty($settings)
        ) {
            if (isset($service['slug']) && !empty($service['slug'])) {
                if (
                    isset($current_service) && ($current_service != false) &&
                    ($current_service == $service['slug'])
                ) {
                    if (acoofm_update_option('credentials', $credentials)) {
                        return new WP_REST_Response(array('message' => __('Configuration Saved', 'offload-media-cloud-storage'), 'previousService' => true), 200);
                        acoofm_clear_cache();
                    } else {
                        return new WP_REST_Response(array('message' => __('Something went wrong', 'offload-media-cloud-storage'), 'previousService' => false), 403);
                    }
                } else if (
                    acoofm_update_option('service', $service) &&
                    acoofm_update_option('credentials', $credentials) &&
                    acoofm_update_option('settings', $settings)
                ) {
                    acoofm_clear_cache();
                    return new WP_REST_Response(array('message' => __('Configuration Saved', 'offload-media-cloud-storage'), 'previousService' => false), 200);
                } else {
                    return new WP_REST_Response(array('message' => __('Something went wrong', 'offload-media-cloud-storage'), 'previousService' => false), 403);
                }
            } else {
                return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'previousService' => false), 405);
            }
        } else {
            return new WP_REST_Response(array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'previousService' => false), 405);
        }
    }

    /**
     * Save the settings
     * @since 1.0.0
     */
    public function settingsSave($request)
    {
        $settings = $request->get_param('settings');
        if (isset($settings) && !empty($settings)) {
            // Validate URLS
            if (
                isset($settings['enable_cdn']) && $settings['enable_cdn'] == true &&
                isset($settings['cdn_url']) && !empty($settings['cdn_url'])
            ) {
                if (filter_var($settings['cdn_url'], FILTER_VALIDATE_URL) === false) {
                    return new WP_REST_Response(__('Invalid CDN url. Please enter a valid url.', 'offload-media-cloud-storage'), 400);
                } else if(is_ssl() && strpos($settings['cdn_url'], 'https') !== 0) {
                    return new WP_REST_Response(__('Unsuitable CDN URL. <small>An SSL-enabled site should use an SSL-enabled CDN URL.</small>', 'offload-media-cloud-storage'), 400);
                }
            }

            if (acoofm_update_option('settings', $settings)) {
                acoofm_clear_cache();
                return new WP_REST_Response(__('Settings updated', 'offload-media-cloud-storage'), 200);
            } else {
                return new WP_REST_Response(__('Something went wrong', 'offload-media-cloud-storage'), 403);
            }
        } else {
            return new WP_REST_Response(__('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 405);
        }
    }

    /**
     * Reset the settings
     * @since 1.0.0
     */
    public function settingsReset($request)
    {
        $settings = $request->get_param('settings');
        if (isset($settings) && !empty($settings)) {
            if (acoofm_update_option('settings', $settings)) {
                acoofm_clear_cache();
                return new WP_REST_Response(__('Settings reset to default settings', 'offload-media-cloud-storage'), 200);
            } else {
                return new WP_REST_Response(__('Something went wrong', 'offload-media-cloud-storage'), 403);
            }
        } else {
            return new WP_REST_Response(__('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 405);
        }
    }

    /**
     * Upload Credentials
     * @since 1.0.0
     */
    public function uploadCredentials($request)
    {
        if (isset($_FILES['file']) && !empty($_FILES['file'])) {
            $config_file = $_FILES['file'];
            if (isset($config_file['type']) && $config_file['type'] == 'application/json') {
                if (file_exists($config_file["tmp_name"])) {

                    add_filter( 'upload_dir', array($this, 'change_file_upload_dir'));
                    add_filter( 'mime_types', array($this, 'add_custom_mime_type_json'));

                    if ( ! function_exists( 'wp_handle_upload' ) ) {
                        require_once( ABSPATH . 'wp-admin/includes/file.php' );
                    }

                    $upload_overrides = array(
                        'test_form' => false,
                        'test_type' => true,
                        'mimes'     => array ( 'json'=>'application/json' )
                    );

                    $movefile = wp_handle_upload( $config_file, $upload_overrides );

                    remove_filter( 'upload_dir', array($this, 'change_file_upload_dir'));
                    remove_filter( 'mime_types', array($this, 'add_custom_mime_type_json'));

                    if ($movefile && !isset($movefile['error'])) {
                        $result     = array(
                            'success' => true,
                            'file_path' => $movefile['file'],
                            'file_name' => basename($movefile['file']),
                        );
                        return new WP_REST_Response($result, 200);
                    } else {
                        return new WP_REST_Response($movefile['error'], 415);
                    }
                }
            } else {
                return new WP_REST_Response(__('Invalid file format', 'offload-media-cloud-storage'), 415);
            }
        } else {
            return new WP_REST_Response(__('Insufficient data. Please try again', 'offload-media-cloud-storage'), 405);
        }
    }


    /**
     * Change File Upload Directory
     * @since 1.0.0
     */
    public function change_file_upload_dir($upload) {
        // $upload_dir = wp_get_upload_dir();
        $path   = $upload['basedir'] . '/' . ACOOFM_UPLOADS;
        $url    = $upload['baseurl'] . '/' . ACOOFM_UPLOADS;

        if (!is_dir($path)) {
            mkdir($path);
        }   

        $upload['subdir'] = '/' . ACOOFM_UPLOADS;
        $upload['path'] = $upload['basedir'] . '/' . ACOOFM_UPLOADS;
        $upload['url'] = $upload['baseurl'] . '/' . ACOOFM_UPLOADS;

        return $upload;
    }

    /**
     * Add Custom Mime Type JSON
     * @since 1.0.0
     */
    public function add_custom_mime_type_json($mimes) {
        $mimes['json'] = 'application/json';
        // Return the array back to the function with our added mime type.
        return $mimes;
    }
    

    /**
     *
     * Ensures only one instance of APIFW is loaded or can be loaded.
     *
     * @param string $file Plugin root path.
     * @return Main APIFW instance
     * @see WordPress_Plugin_Template()
     * @since 1.0.0
     * @static
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Permission Callback
     **/
    public function getPermission()
    {
        if (current_user_can('administrator')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }
}
