<?php

/**
 * Load All GOOGLE related actions
 *
 * @class   ACOOFMF_GOOGLE
 *
 * It is used to divide functionality of google cloud connection into different parts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Libraries
use Google\Cloud\Storage\StorageClient;

class ACOOFMF_GOOGLE
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
     * The google client.
     *
     * @var     object
     * @access  public
     * @since   1.0.0
     */

    public $acoofm_google_client = false;

    /**
     * The google bucket object.
     *
     * @var     object
     * @access  public
     * @since   1.0.0
     */

    public $acoofm_google_bucket = false;

    /**
     * The google bucket name.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */

    public $acoofm_google_bucket_name = '';

    /**
     * The google bucket name.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */

    protected $acoofm_google_config_path = '';

    /**
     * The plugin hook suffix.
     *
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $hook_suffix = array();

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

        if (
            isset($this->config['config_json']) && !empty($this->config['config_json']) &&
            isset($this->config['google_bucket_name']) && !empty($this->config['google_bucket_name']) &&
            isset($this->config['config_json']['path']) && !empty($this->config['config_json']['path'])
        ) {
            $this->acoofm_google_bucket_name = $this->config['google_bucket_name'];
            $this->acoofm_google_config_path = $this->config['config_json']['path'];

            if(file_exists($this->acoofm_google_config_path)) {
                $this->acoofm_google_client = new StorageClient([
                    'keyFilePath' => $this->acoofm_google_config_path,
                ]);
                $this->acoofm_google_bucket = $this->acoofm_google_client->bucket($this->config['google_bucket_name']);
            } else {
                add_action('admin_notices', function (){
                    echo "<div class='error'><p><strong>".__('Offload Media', 'offload-media-cloud-storage').": </strong><br>Google Cloud Storage configuration file missing from the directory.
                    <br>It may break the media url's as well as media uploads.<br>
                    <a href='".admin_url('admin.php?page='.$this->token . '-admin-ui#/configure')."'>Re-configure</a> plugin to fix the issue.
                    </p></div>";
                });
            }
        }
    }

    /**
     * Verify Credentials
     * @since 1.0.0
     * @return boolean
     */
    public function verify($config_file, $bucket_name)
    {
        if (
            isset($config_file) && !empty($config_file) &&
            isset($config_file['path']) && !empty($config_file['path']) &&
            isset($bucket_name) && !empty($bucket_name)
        ) {
            try {
                $googleClient = new StorageClient([
                    'keyFilePath' => $config_file['path'],
                ]);

                $bucket = $googleClient->bucket($bucket_name);
                if ($bucket->exists()) {
                    $bucket_found = true;
                } else {
                    return array('message' => __('No Buckets found', 'offload-media-cloud-storage'), 'code' => 403, 'success' => false);
                }
                if ($bucket_found) {
                    $fileName = "acoofm_verify.txt";
                    $verify_file = fopen('./' . $fileName, "w");
                    $txt = "We are verifying input/output operations in s3\n";
                    fwrite($verify_file, $txt);
                    fclose($verify_file);

                    $upload = $bucket->upload(
                        fopen('./' . $fileName, 'r'),
                        [
                            'name' => $fileName,
                            'predefinedAcl' => 'publicRead',
                        ]
                    );

                    @unlink('./' . $fileName);

                    $object = $bucket->object($fileName);
                    if ($object->exists()) {
                        $object->downloadToFile('./' . $fileName);
                        if (file_exists($fileName)) {
                            @unlink('./' . $fileName);
                            $object->delete();

                            if (!$object->exists()) {
                                return array('message' => __('Configuration for Googlecloud Storage verified successfully', 'offload-media-cloud-storage'), 'code' => 200, 'success' => true);
                            } else {
                                return array('message' => __('Bucket has permission issues on deleting the object from bucket, Please check permission as well as policies', 'offload-media-cloud-storage'), 'code' => 403, 'success' => false);
                            }
                        } else {
                            return array('message' => __('Bucket has permission issues on getting the object from bucket, Please check permission as well as policies', 'offload-media-cloud-storage'), 'code' => 403, 'success' => false);
                        }
                    } else {
                        return array('message' => __('Bucket has permission issues on putting object in to bucket, Please check permission as well as policies', 'offload-media-cloud-storage'), 'code' => 403, 'success' => false);
                    }
                } else {
                    return array('message' => __('Bucket Name is incorrect', 'offload-media-cloud-storage'), 'code' => 403, 'success' => false);
                }
            } catch (Exception $ex) {
                return array('message' => $ex->getMessage(), 'code' => $ex->getCode(), 'success' => false);
            }
        } else {
            return array('message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'), 'code' => 405, 'success' => false);
        }
    }

    /**
     * Connect Function To Identify the congfigurations are correct
     * @since 1.0.0
     */
    public function connect()
    {
        if ($this->acoofm_google_client) {
            return true;
        }
        return false;
    }

    
    /**
     * Check the object exist 
     * @since 1.1.8
     */
    public function is_exist($key) {
        if(!$key) return false;
        
        $object = $this->acoofm_google_bucket->object($key);
        if ($object->exists()) {
            return true;
        }
        
        return false;
    }


    
    /**
     * Make Object Private
     * @since 3.0.0
     * 
     */
    public function make_private($key) {
        if(!$key) return false;
        $object = $this->acoofm_google_bucket->object($key);
        if ($object->exists()) {
            $object->update(['acl' => []], ['predefinedAcl' => 'private']);
            return true;
        }
        return false;
    }



    /**
     * Make Object Public
     * @since 3.0.0
     * 
     */
    public function make_public($key) {
        if(!$key) return false;
        $object = $this->acoofm_google_bucket->object($key);
        if ($object->exists()) {
            $object->update(['acl' => []], ['predefinedAcl' => 'publicRead']);
            return true;
        }
        return false;
    }


    /**
     * Upload Single
     * @since 1.0.0
     * @return boolean
     */
    public function uploadSingle($media_absolute_path, $media_path, $prefix='')
    {
        $result = array();
        if (
            isset($media_absolute_path) && !empty($media_absolute_path) &&
            isset($media_path) && !empty($media_path)
        ) {
            $file_name = wp_basename( $media_path );
            if ($file_name) {
                $upload_path = acoofm_generate_object_key($file_name, $prefix);

                // Decide Multipart upload or normal put object
                if (filesize($media_absolute_path) <= ACOOFM_GOOGLE_MULTIPART_UPLOAD_MINIMUM_SIZE) {
                    // Upload a publicly accessible file. The file size and type are determined by the SDK.
                    try {

                        $upload = $this->acoofm_google_bucket->upload(
                            fopen($media_absolute_path, 'r'),
                            [
                                'name' => $upload_path,
                                'predefinedAcl' => 'publicRead',
                            ]
                        );

                        $object = $this->acoofm_google_bucket->object($upload_path);

                        if ($object->exists()) {
                            $result = array(
                                'success' => true,
                                'code' => 200,
                                'file_url' => $this->generate_file_url($upload_path),
                                'key' => $upload_path,
                                'Message' => __('File Uploaded Successfully', 'offload-media-cloud-storage'),
                            );
                        } else {
                            $result = array(
                                'success' => false,
                                'code' => 403,
                                'Message' => __('Object not found at server.', 'offload-media-cloud-storage'),
                            );
                        }
                    } catch (Exception $e) {
                        $result = array(
                            'success' => false,
                            'code' => $e->getCode(),
                            'Message' => $e->getMessage(),
                        );
                    }
                } else {
                    try {

                        $upload = $this->acoofm_google_bucket->upload(
                            fopen($media_absolute_path, 'r'),
                            [
                                'name' => $upload_path,
                                'predefinedAcl' => 'publicRead',
                                'chunkSize' => 262144 * 2,
                            ]
                        );

                        $object = $this->acoofm_google_bucket->object($upload_path);

                        if ($object->exists()) {
                            $result = array(
                                'success' => true,
                                'code' => 200,
                                'file_url' => $this->generate_file_url($upload_path),
                                'key' => $upload_path,
                                'Message' => __('File Uploaded Successfully', 'offload-media-cloud-storage'),
                            );
                        } else {
                            $result = array(
                                'success' => false,
                                'code' => 403,
                                'Message' => __('Something happened while uploading to server', 'offload-media-cloud-storage'),
                            );
                        }
                    } catch (Exception $e) {
                        $result = array(
                            'success' => false,
                            'code' => $e->getCode(),
                            'Message' => $e->getMessage(),
                        );
                    }
                }
            } else {
                $result = array(
                    'success' => false,
                    'code' => 403,
                    'Message' => __('Check the file you are trying to upload. Please try again', 'offload-media-cloud-storage'),
                );
            }
        } else {
            $result = array(
                'success' => false,
                'code' => 405,
                'Message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'),
            );
        }
        return $result;
    }

    /**
     * Save object to local
     * @since 1.0.0
     */
    public function object_to_local($key, $save_path)
    {
        try {
            $object = $this->acoofm_google_bucket->object($key);
            if ($object->exists()) {
                $object->downloadToFile($save_path);
                if (file_exists($save_path)) {
                    return true;
                }
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }

    /**
     * Delete Single
     * @since 1.0.0
     * @return boolean
     */
    public function deleteSingle($key)
    {
        $result = array();
        if (isset($key) && !empty($key)) {
            try {
                $object = $this->acoofm_google_bucket->object($key);
                $object->delete();

                if (!$object->exists()) {
                    $result = array(
                        'success' => true,
                        'code' => 200,
                        'Message' => __('Deleted Successfully', 'offload-media-cloud-storage'),
                    );
                } else {
                    $result = array(
                        'success' => false,
                        'code' => 403,
                        'Message' => __('File not deleted', 'offload-media-cloud-storage'),
                    );
                }
            } catch (Exception $e) {
                $result = array(
                    'success' => false,
                    'code' => $e->getCode(),
                    'Message' => $e->getMessage(),
                );
            }
        } else {
            $result = array(
                'success' => false,
                'code' => 405,
                'Message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'),
            );
        }
        return $result;
    }

    /**
     * get presigned URL
     * @since 1.0.0
     * @return boolean
     */
    public function get_presigned_url($key)
    {
        $result = array();
        if (isset($key) && !empty($key)) {
            try {
                $object = $this->acoofm_google_bucket->object($key);

                $expires = isset($this->settings['presigned_expire']) ? $this->settings['presigned_expire'] : 20;

                $presignedUrl =  $object->signedUrl(new \DateTime(sprintf('+%s  minutes', $expires)));

                if ($presignedUrl) {
                    $result = array(
                        'success' => true,
                        'code' => 200,
                        'file_url' => $presignedUrl,
                        'Message' => __('Got Presigned URL Successfully', 'offload-media-cloud-storage'),
                    );
                } else {
                    $result = array(
                        'success' => false,
                        'code' => 403,
                        'Message' => __('Error getting presigned URL', 'offload-media-cloud-storage'),
                    );
                }
            } catch (Exception $e) {
                $result = array(
                    'success' => false,
                    'code' => $e->getCode(),
                    'Message' => $e->getMessage(),
                );
            }
        } else {
            $result = array(
                'success' => false,
                'code' => 405,
                'Message' => __('Insufficient Data. Please try again', 'offload-media-cloud-storage'),
            );
        }
        return $result;
    }

    private function generate_file_url($key)
    {
        $url_base = 'https://storage.googleapis.com';

        return apply_filters('acoofm_generate_google_file_url',
            $url_base . '/' . $this->acoofm_google_bucket_name . '/' . $key,
            $url_base, $key,
            $this->acoofm_google_bucket_name
        );
    }
}
