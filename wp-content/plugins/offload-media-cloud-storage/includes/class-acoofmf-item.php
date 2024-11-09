<?php

/**
 * Manage All Server Uploaded Items
 *
 * @class   ACOOFMF_ITEM
 *
 * It is used to divide functionality of database items
 */

if (!defined('ABSPATH')) {
    exit;
}

// Libraries

class ACOOFMF_ITEM
{

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

    /**
     * The main plugin file.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin Configuration.
     *
     * @var     array
     * @access  protected
     * @since   1.0.0
     */

    protected $config;

    /**
     * The plugin Settings.
     *
     * @var     array
     * @access  protected
     * @since   1.0.0
     */

    protected $settings;

    /**
     * The plugin assets URL.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */

    public $assets_url;

    /**
     * The plugin hook suffix.
     *
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $hook_suffix = array();

    /**
     * Service Bucket name.
     *
     * @var     string
     * @access  protected
     * @since   1.0.0
     */
    protected $bucket_name = '';

    /**
     * Service Bucket Region.
     *
     * @var     string
     * @access  protected
     * @since   1.0.0
     */
    protected $region = '';

    /**
     * Service
     *
     * @var     string
     * @access  protected
     * @since   1.0.0
     */
    protected $service = '';

    /**
     * Constructor function.
     *
     * @access  public
     * @param string $file plugin start file path.
     * @since   1.0.0
     */
    public function __construct($file = '')
    {
        $this->version = ACOOFM_VERSION;
        $this->token = ACOOFM_TOKEN;
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));
        $plugin = plugin_basename($this->file);
        $this->config = acoofm_get_option('credentials', []);
        $this->settings = acoofm_get_option('settings', []);
        $this->service = acoofm_get_service('slug');

        if (isset($this->config['bucket_name'])) {
            $this->bucket_name = $this->config['bucket_name'];
        }
        if (isset($this->config['region'])) {
            $this->region = $this->config['region'];
        }
    }

    /**
     * Add Item In database
     * @since 1.0.0
     * @param
     */
    public function add(
        $source_id,
        $url,
        $path,
        $source_path,
        $meta,
        $source_type = 'media-library',
        $is_private = 0
    ) {
        global $wpdb;
        $item_id = false;

        $data = array(
            'provider' => $this->service,
            'region' => $this->region,
            'bucket' => $this->bucket_name,
            'source_id' => $source_id,
            'source_path' => $source_path,
            'source_type' => $source_type,
            'url' => $url,
            'path' => $path,
            'is_private' => $is_private,
            'extra_info' => maybe_serialize($meta),
        );
        if ($wpdb->insert(ACOOFM_ITEM_TABLE, $data)) {
            $item_id = $wpdb->insert_id;
            $data['id'] = $item_id;
            acoofm_set_cache($source_id, $data, $source_id);
            add_post_meta((int) $source_id, ACOOFM_ATTACHMENT_META_KEY, $item_id);
        }

        return $item_id;
    }

    /**
     * Get Item from database
     * @since 1.0.0
     * @param
     */
    public function get($source_id)
    {
        global $wpdb;
        if (isset($source_id) && !empty($source_id)) {
            $data = acoofm_get_cache($source_id, $source_id);
            if ($data === false) {
                $results = $wpdb->get_results("SELECT * FROM " . ACOOFM_ITEM_TABLE . " WHERE source_id = $source_id LIMIT 1", ARRAY_A);
                if ($wpdb->last_error || null === $results || !(isset($results) && !empty($results))) {
                    return false;
                }
                $data = $results[0];
                acoofm_set_cache($source_id, $data, $source_id);
            }
            return $data;
        }
        return false;
    }

    /**
     * Get service url of item from database
     * @since 1.0.0
     * @param
     */
    public function get_url($source_id)
    {
        if ($data = $this->get($source_id)) {
            if (
                isset($this->settings['enable_presigned']) && $this->settings['enable_presigned'] &&
                isset($this->settings['presigned_expire']) && !empty($this->settings['presigned_expire']) &&
                isset($data['path']) && !empty($data['path'])
            ) {
                $preSignedUrl = acoofm_get_cache('presigned_url_full_' . $source_id, $source_id);
                if ($preSignedUrl === false) {
                    global $acoofmService;
                    $result = $acoofmService->get_presigned_url($data['path']);

                    if (!empty($result)) {
                        if (isset($result['success']) && $result['success']) {
                            $preSignedUrl = $this->may_generate_cdn_url($result['file_url'], $data['path']);
                            $expireMinutes = (int) $this->settings['presigned_expire'];
                            $expireSeconds = $expireMinutes * 60;

                            acoofm_set_cache('presigned_url_full_' . $source_id, $preSignedUrl, $source_id, $expireSeconds);
                        }
                    }
                }
                return $preSignedUrl;
            } else if (isset($data['url']) && !empty($data['url'])) {
                return $this->may_generate_cdn_url($data['url'], $data['path']);
            }
        }
        return false;
    }

    /**
     * Get extra values of item from database
     * @since 1.0.0
     * @param
     */
    public function get_extras($source_id)
    {

        if ($data = $this->get($source_id)) {
            if (isset($data['extra_info']) && !empty($data['extra_info'])) {
                return unserialize($data['extra_info']);
            }
        }
        return false;
    }

    /**
     * Get service path of item from database
     * @since 1.0.0
     * @param
     */
    public function get_path($source_id)
    {
        if ($data = $this->get($source_id)) {
            if (isset($data['path']) && !empty($data['path'])) {
                return $data['path'];
            }
        }
        return false;
    }

    /**
     * Get service path of item from database
     * @since 1.0.0
     * @param
     */
    public function moveToLocal($source_id, $size = 'full', $all = false)
    {
        $local_files = array();
        $localfile   = false;
        $upload_dir  = wp_get_upload_dir();

        if ($data = $this->get((int)$source_id)) {
            global $acoofmService;
            if($all) {
                if (
                    isset($data['source_path']) && !empty($data['source_path']) &&
                    isset($data['path']) && !empty($data['path'])
                ) {
                    $file_path = trailingslashit($upload_dir['basedir']) . $data['source_path'];
                    if(!file_exists($file_path)) {
                        if($acoofmService->object_to_local($data['path'], $file_path)) {
                            $local_files['full']   = $file_path;
                        }
                    } else {
                        $local_files['full']   = $file_path;
                    }
                }
                $extras = $acoofmItem->get_extras((int) $source_id) ? $acoofmItem->get_extras((int) $source_id) : [];
                if (isset($extras['sizes']) && !empty($extras['sizes'])) {
                    $sizes = $extras['sizes'];
                    if(isset($sizes[$size]) && !empty($sizes[$size])) {
                        $sub_file_path = trailingslashit($upload_dir['basedir']) . $sizes[$size]['source_path'];
                        if(!file_exists($sub_file_path)) {
                            if($acoofmService->object_to_local($sizes[$size]['path'], $sub_file_path)) {
                                $local_files[$size]   = $sub_file_path;
                            }
                        } else {
                            $local_files[$size]   = $sub_file_path;
                        }
                    }
                }
                return !empty($local_files) ? $local_files : false;
            } else {
                if($size === 'full') {
                    if (
                        isset($data['source_path']) && !empty($data['source_path']) &&
                        isset($data['path']) && !empty($data['path'])
                    ) {
                        $file_path = trailingslashit($upload_dir['basedir']) . $data['source_path'];
                        $moved     = true;
                        if(!file_exists($file_path)) {
                            $moved  = $acoofmService->object_to_local($data['path'], $file_path);
                        } 
                        if($moved){
                            $localfile = $file_path;
                        }
                    }
                } else {
                    $extras = $acoofmItem->get_extras((int) $source_id) ? $acoofmItem->get_extras((int) $source_id) : [];
                    if (isset($extras['sizes']) && !empty($extras['sizes'])) {
                        $sizes = $extras['sizes'];
                        if(isset($sizes[$size]) && !empty($sizes[$size])) {
                            $sub_file_path = trailingslashit($upload_dir['basedir']) . $sizes[$size]['source_path'];
                            $moved  = true;
                            if(!file_exists($sub_file_path)) {
                                $moved  = $acoofmService->object_to_local($sizes[$size]['path'], $sub_file_path);
                            } 
                            if($moved) {
                                $localfile = $sub_file_path;
                            }
                        }
                    }
                } 
                return $localfile;
            }
        }
        return false;
    }

    /**
     * Get service url of item from database
     * @since 1.0.0
     *
     */
    public function get_thumbnail_url($source_id, $size = 'thumbnail')
    {
        if ($data = $this->get($source_id)) {
            if ($size === 'full') {
                if (
                    isset($this->settings['enable_presigned']) && $this->settings['enable_presigned'] &&
                    isset($data['path']) && !empty($data['path'])
                ) {
                    $preSignedUrl = acoofm_get_cache('presigned_url_full_' . $source_id, $source_id);
                    if ($preSignedUrl === false) {

                        global $acoofmService;
                        $result = $acoofmService->get_presigned_url($data['path']);

                        if (!empty($result)) {
                            if (isset($result['success']) && $result['success']) {
                                $preSignedUrl = $this->may_generate_cdn_url($result['file_url'], $data['path']);
                                $expireMinutes = (int) (isset($this->settings['presigned_expire']) && !empty($this->settings['presigned_expire']))
                                ? $this->settings['presigned_expire']
                                : 20;
                                $expireSeconds = $expireMinutes * 60;

                                acoofm_set_cache('presigned_url_full_' . $source_id, $preSignedUrl, $source_id, $expireSeconds);
                            }
                        }
                    }
                    return $preSignedUrl;
                } else if (isset($data['url']) && !empty($data['url'])) {
                    return $this->may_generate_cdn_url($data['url'], $data['path']);
                }
                return false;
            } else if (isset($data['extra_info']) && !empty($data['extra_info'])) {
                $extra = unserialize($data['extra_info']);
                if (
                    isset($extra) && !empty($extra) &&
                    isset($extra['sizes']) && !empty($extra['sizes']) &&
                    isset($extra['sizes'][$size]) && !empty($extra['sizes'][$size])
                ) {
                    $thumb = $extra['sizes'][$size];
                    if (
                        isset($this->settings['enable_presigned']) && $this->settings['enable_presigned'] &&
                        isset($thumb['path']) && !empty($thumb['path'])
                    ) {
                        $preSignedUrl = acoofm_get_cache('presigned_url_' . $size . '_' . $source_id, $source_id);
                        if ($preSignedUrl === false) {
                            global $acoofmService;
                            $result = $acoofmService->get_presigned_url($thumb['path']);

                            if (!empty($result)) {
                                if (isset($result['success']) && $result['success']) {
                                    $preSignedUrl = $this->may_generate_cdn_url($result['file_url'], $thumb['path']);
                                    $expireMinutes = (int) (isset($this->settings['presigned_expire']) && !empty($this->settings['presigned_expire']))
                                    ? $this->settings['presigned_expire']
                                    : 20;
                                    $expireSeconds = $expireMinutes * 60;

                                    acoofm_set_cache('presigned_url_' . $size . '_' . $source_id, $preSignedUrl, $source_id, $expireSeconds);
                                }
                            }
                        }
                        return $preSignedUrl;
                    } else if (isset($thumb['url']) && !empty($thumb['url'])) {
                        return $this->may_generate_cdn_url($thumb['url'], $thumb['path']);
                    }
                } else if(
                    $size == 'original' &&
                    isset($extra) && !empty($extra) &&
                    isset($extra['original']) && !empty($extra['original'])
                ) {
                    $original = $extra['original'];
                    if (
                        isset($this->settings['enable_presigned']) && $this->settings['enable_presigned'] &&
                        isset($original['key']) && !empty($original['key'])
                    ) {
                        $preSignedUrl = acoofm_get_cache('presigned_url_original_' . $source_id, $source_id);
                        if ($preSignedUrl === false) {
                            global $acoofmService;
                            $result = $acoofmService->get_presigned_url($original['key']);

                            if (!empty($result)) {
                                if (isset($result['success']) && $result['success']) {
                                    $preSignedUrl = $this->may_generate_cdn_url($result['file_url'], $original['key']);
                                    $expireMinutes = (int) (isset($this->settings['presigned_expire']) && !empty($this->settings['presigned_expire']))
                                    ? $this->settings['presigned_expire']
                                    : 20;
                                    $expireSeconds = $expireMinutes * 60;

                                    acoofm_set_cache('presigned_url_original_' . $source_id, $preSignedUrl, $source_id, $expireSeconds);
                                }
                            }
                        }
                        return $preSignedUrl;
                    } else if (isset($original['file_url']) && !empty($original['file_url'])) {
                        return $this->may_generate_cdn_url($original['file_url'], $original['key']);
                    }
                }
            }
        }
        return false;
    }

    /**
     * Function to update item in data base
     * @since 1.0.0
     */
    public function update($source_id, $data)
    {
        global $wpdb;
        if (isset($source_id) && !empty($source_id)) {

            $data['provider'] = $this->service;
            $data['region'] = $this->region;
            $data['bucket'] = $this->bucket_name;

            $rows = $wpdb->update(ACOOFM_ITEM_TABLE, $data, array('source_id' => $source_id));
            if ($wpdb->last_error || false === $rows) {
                return false;
            }

            acoofm_delete_cache($source_id, $source_id);
            $current_data = $this->get($source_id);
            return true;
        }
        return false;
    }

    /**
     * Is Private
     * @since 1.1.8
     */
    public function is_private($source_id) {
        global $wpdb;
        if (isset($source_id) && !empty($source_id)) {
            $attachmentItem = $this->get($source_id);
            if(empty($attachmentItem)) return false;
            
            return !!(int)$attachmentItem['is_private'];
        }
        return false;
    }
    

    /**
     * Function to delete item from data base
     * @since 1.0.0
     */
    public function delete($source_id)
    {
        global $wpdb;
        if (isset($source_id) && !empty($source_id)) {
            $rows = $wpdb->delete(ACOOFM_ITEM_TABLE, array('source_id' => $source_id));
            if ($wpdb->last_error || false === $rows) {
                return false;
            }
            acoofm_delete_cache($source_id, $source_id);
            delete_post_meta($source_id, ACOOFM_ATTACHMENT_META_KEY);
            return true;
        }
        return false;
    }

    /**
     * Get similar existing files like origin path
     * @since 1.0.0
     */
    public function get_similar_files_by_path($path)
    {
        global $wpdb;
        if (isset($path) && !empty($path)) {
            $results = $wpdb->get_results("SELECT source_path FROM " . ACOOFM_ITEM_TABLE . " WHERE source_path LIKE '$path%'", ARRAY_A);
            if ($wpdb->last_error || null === $results || !(isset($results) && !empty($results))) {
                return false;
            }
            $source_paths = array();
            foreach ($results as $row) {
                $source_paths[] = $row['source_path'];
            }
            return $source_paths;
        }
        return false;
    }

    /**
     * Get image URLS by path info of URL
     * @since 1.0.0
     */
    public function get_images_by_files($files)
    {
        global $wpdb;

        $sql = array('0'); // Stop errors when filenames are empty

        if (isset($files) && !empty($files)) {
            $sql = array('0');

            foreach ($files as $file) {
                if (isset($file['basename']) && !empty($file['basename'])) {
                    $sql[] = 'source_path LIKE "%' . $file['basename'] . '" OR path LIKE "%' . $file['basename'] . '" OR extra_info LIKE "%' . $file['basename'] . '%"';
                }
            }
            $results = $wpdb->get_results('SELECT * FROM ' . ACOOFM_ITEM_TABLE . ' WHERE ' . implode(" OR ", $sql), ARRAY_A);
            if ($wpdb->last_error || null === $results || !(isset($results) && !empty($results))) {
                return false;
            }

            $images = array();

            foreach ($results as $item) {
                if (isset($item['extra_info']) && !empty($item['extra_info'])) {
                    $extra = unserialize($item['extra_info']);
                    if (isset($extra) && !empty($extra)) {
                        if (isset($extra['sizes']) && !empty($extra['sizes'])) {
                            foreach ($extra['sizes'] as $key => $size) {
                                $images[$item['source_id']]['sizes'][$key]['url'] = $size['url'];
                            }
                        }
                    }
                }

                $images[$item['source_id']]['url'] = $item['url'];
            }
            return $images;
        }
        return false;
    }

    /**
     * Checking Item that is served by provider And Is rewrite URL is enabled
     * @since 1.0.0
     * @param
     */
    public function is_available_from_provider($source_id, $check_rewrite = true)
    {
        $item_id = acoofm_get_post_meta($source_id, ACOOFM_ATTACHMENT_META_KEY, true, true);
        if(!(isset($item_id) && !empty($item_id))) {
            return false;
        }

        $provider = $this->get_provider($source_id);
        if (
            isset($provider) && !empty($provider) &&
            isset($provider['slug']) && !empty($provider['slug']) &&
            $provider['slug'] == $this->service
        ) {
            if(
                ($check_rewrite && (isset($this->settings['rewrite_url']) && $this->settings['rewrite_url'])) ||
                !$check_rewrite
            ) {
                return true;
            } 
        }
        return false;
    }

    /**
     * Get item specific provider
     * @since 1.0.0
     * @param
     */
    public function get_provider($source_id)
    {
        if ($data = $this->get($source_id)) {
            if (isset($data['provider'])) {
                $provider_name = '';
                $provider = $data['provider'];

                switch ($provider) {
                    case 's3':
                        $provider_name = __('AWS/S3', 'offload-media-cloud-storage');
                        break;
                    case 'google':
                        $provider_name = __('Google Cloud Storage', 'offload-media-cloud-storage');
                        break;
                    case 'ocean':
                        $provider_name = __('Digital Ocean Spaces', 'offload-media-cloud-storage');
                        break;
                    default:
                        $provider_name = '';
                }

                return array('slug' => $provider, 'label' => $provider_name);
            }
        }
        return false;
    }

    /**
     * Checking Item that is served by provider
     * @since 1.0.0
     * @param
     */
    public function get_region($source_id)
    {
        if ($data = $this->get($source_id)) {
            if (isset($data['region'])) {
                return $data['region'];
            }
        }
        return false;
    }

    /**
     * CDN url generate
     * @since 1.0.0
     *
     */
    public function may_generate_cdn_url($url, $path)
    {
        if (
            isset($this->settings['enable_cdn']) && $this->settings['enable_cdn'] &&
            isset($this->settings['cdn_url']) && !empty($this->settings['cdn_url']) &&
            isset($url) && !empty($url) &&
            isset($path) && !empty($path)
        ) {

            $urlArray =  explode($path, $url );
            if(isset($urlArray) && !empty($urlArray) && isset($urlArray[0])) {
                $new_url = str_replace(trailingslashit($urlArray[0]), trailingslashit($this->settings['cdn_url']), $url);
                $new_url = apply_filters('acoofm_cdn_url_setting', $new_url, $url, $this->settings['cdn_url']);
                $new_url = preg_replace('/([^:])(\/{2,})/', '$1/', $new_url); // to remove double slash trapped during any operations
                return $new_url;
            }

        }
        return $url;
    }
}
