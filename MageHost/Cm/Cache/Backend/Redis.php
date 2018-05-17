<?php
/**
 * MageHost_Hosting
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this Module to
 * newer versions in the future.
 *
 * @category     MageHost
 * @package      MageHost_Hosting
 * @copyright    Copyright (c) 2015 MageHost BVBA (http://www.magentohosting.pro)
 */
/**
 * {@inheritdoc}
 */
class MageHost_Cm_Cache_Backend_Redis extends Cm_Cache_Backend_Redis
{
    const ADMIN_READ_TIMEOUT = 7200;
    /** @var bool - Only true when constructor was successful. */
    protected $works = false;
    /** @var string|null */
    protected $frontendPrefix = null;

    /**
     * This constructor is executed in a very early stage, during @see Mage_Core_Model_App->initCache()
     * Only few things of the Magento framework will be initialized at this time.
     *
     * {@inheritdoc}
     */
    public function __construct( $options ) {
        if ( $this->isAdmin() ) {
            if ( empty($options['read_timeout']) || $options['read_timeout'] < self::ADMIN_READ_TIMEOUT ) {
                $options['read_timeout'] = self::ADMIN_READ_TIMEOUT;
            }
        }
        try {
            parent::__construct( $options );
            $this->works = true;
        } catch ( CredisException $e ) {
            $this->processRedisException( $e, 'constructor' );
        } catch ( RedisException $e ) {
            $this->processRedisException( $e, 'constructor' );
        } catch ( Zend_Cache_Exception $e ) {
            $this->processRedisException( $e, 'constructor' );
        }
        if ( !$this->works ) {
            // We can't use Zend_Log here because it may not be loaded yet.
            error_log( sprintf( '%s: Disabled Redis cache backend because constructor failed', __CLASS__ ) );
        }
    }

    /**
     * This method will dispatch the event 'magehost_cache_miss_mh' when a cache key miss occurs loading a key
     * from MageHost_BlockCache.
     * This method will return false when constructor failed.
     *
     * This method will also catch exceptions on Redis failure.
     *
     * {@inheritdoc}
     */
    public function load($id, $doNotTestCacheValidity = false) {
        $result = false;
        if ( $this->works ) {
            try {
                $result = parent::load( $id, $doNotTestCacheValidity );
            } catch ( CredisException $e ) {
                $this->processRedisException( $e, 'load' );
                $result = false;
            } catch ( RedisException $e ) {
                $this->processRedisException( $e, 'load' );
                $result = false;
            } catch ( Zend_Cache_Exception $e ) {
                $this->processRedisException( $e, 'load' );
                $result = false;
            }
        }
        if ( false === $result && false !== strpos($id,'_JV_') ) {
            Mage::dispatchEvent('jv_cache_miss_jv', array('id' => $id)); // deprecated
            Mage::dispatchEvent('magehost_cache_miss_jv', array('id' => $id));
        }
        if ( false === $result && false !== strpos($id,'_MH_') ) {
            Mage::dispatchEvent('magehost_cache_miss_mh', array('id' => $id));
        }
        return $result;
    }

    /**
     * This method will catch exceptions on Redis failure.
     * This method will return false when constructor failed.
     *
     * {@inheritdoc}
     */
    public function test($id) {
        $result = false;
        if ( $this->works ) {
            try {
                $result = parent::test( $id );
            } catch ( CredisException $e ) {
                $this->processRedisException( $e, 'test' );
                $result = false;
            } catch ( RedisException $e ) {
                $this->processRedisException( $e, 'test' );
                $result = false;
            } catch ( Zend_Cache_Exception $e ) {
                $this->processRedisException( $e, 'test' );
                $result = false;
            }
        }
        return $result;
    }

    /**
     * This method will catch exceptions on Redis failure.
     * This method will return false when constructor failed.
     *
     * This method will dispatch the event 'magehost_cache_save_block' when cache is saved for a html block.
     *
     * {@inheritdoc}
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
        $result = false;
        if ( in_array( $this->getFrontendPrefix().'BLOCK_HTML', $tags ) ) {
            $transportObject = new Varien_Object;
            /** @noinspection PhpUndefinedMethodInspection */
            $transportObject->setTags($tags);
            Mage::dispatchEvent('jv_cache_save_block', array('id' => $id,'transport' => $transportObject)); // deprecated
            Mage::dispatchEvent('magehost_cache_save_block', array('id' => $id,'transport' => $transportObject));
            /** @noinspection PhpUndefinedMethodInspection */
            $tags = $transportObject->getTags();
        }
        if ( $this->works ) {
            try {
                $result = parent::save( $data, $id, $tags, $specificLifetime );
            } catch ( CredisException $e ) {
                $this->processRedisException( $e, 'save' );
                $result = false;
            } catch ( RedisException $e ) {
                $this->processRedisException( $e, 'save' );
                $result = false;
            } catch ( Zend_Cache_Exception $e ) {
                $this->processRedisException( $e, 'save' );
                $result = false;
            }
        }
        return $result;
    }

    /**
     * This method will catch exceptions on Redis failure.
     * This method will return false when constructor failed.
     *
     * {@inheritdoc}
     */
    public function remove($id) {
        $result = false;
        if ( $this->works ) {
            try {
                $result = parent::remove( $id );
            } catch ( CredisException $e ) {
                $this->processRedisException( $e, 'remove' );
                $result = false;
            } catch ( RedisException $e ) {
                $this->processRedisException( $e, 'remove' );
                $result = false;
            } catch ( Zend_Cache_Exception $e ) {
                $this->processRedisException( $e, 'remove' );
                $result = false;
            }
        }
        return $result;
    }

    /**
     * This method will dispatch the events 'magehost_clean_backend_cache_before'
     *                                  and 'magehost_clean_backend_cache_after'.
     * Event listeners can change the mode or tags.
     * This method will return false when clean failed.
     *
     * {@inheritdoc}
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        $result = false;
        $transportObject = new Varien_Object;
        /** @noinspection PhpUndefinedMethodInspection */
        $transportObject->setMode( $mode );
        /** @noinspection PhpUndefinedMethodInspection */
        $transportObject->setTags( $tags );
        Mage::dispatchEvent( 'jv_clean_backend_cache', array( 'transport' => $transportObject ) ); // deprecated
        Mage::dispatchEvent( 'magehost_clean_backend_cache_before', array( 'transport' => $transportObject ) );
        /** @noinspection PhpUndefinedMethodInspection */
        $mode = $transportObject->getMode();
        /** @noinspection PhpUndefinedMethodInspection */
        $tags = $transportObject->getTags();
        if ( $this->works ) {
            try {
                $result = parent::clean($mode, $tags);
            } catch ( CredisException $e ) {
                $this->processRedisException( $e, 'remove' );
                $result = false;
            } catch ( RedisException $e ) {
                $this->processRedisException( $e, 'remove' );
                $result = false;
            } catch ( Zend_Cache_Exception $e ) {
                $this->processRedisException( $e, 'remove' );
                $result = false;
            }
        }
        $transportObject->setResult( $result );
        Mage::dispatchEvent( 'magehost_clean_backend_cache_after', array( 'transport' => $transportObject ) );
        $result = $transportObject->getResult();
        return $result;
    }

    /**
     * This method will return an empty array when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getIds() {
        $result = array();
        if ( $this->works ) {
            $result = parent::getIds();
        }
        return $result;
    }

    /**
     * This method will return an empty array when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getTags() {
        $result = array();
        if ( $this->works ) {
            $result = parent::getTags();
        }
        return $result;
    }

    /**
     * This method will return an empty array when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getIdsMatchingTags($tags = array()) {
        $result = array();
        if ( $this->works ) {
            $result = parent::getIdsMatchingTags($tags);
        }
        return $result;
    }

    /**
     * This method will return an empty array when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getIdsNotMatchingTags($tags = array()) {
        $result = array();
        if ( $this->works ) {
            $result = parent::getIdsNotMatchingTags($tags);
        }
        return $result;
    }

    /**
     * This method will return an empty array when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getIdsMatchingAnyTags($tags = array()) {
        $result = array();
        if ( $this->works ) {
            $result = parent::getIdsMatchingAnyTags($tags);
        }
        return $result;
    }

    /**
     * This method will return 0 when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getFillingPercentage() {
        $result = 0;
        if ( $this->works ) {
            $result = parent::getFillingPercentage();
        }
        return $result;
    }

    /**
     * This method will return an empty array when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getMetadatas($id) {
        $result = array();
        if ( $this->works ) {
            $result = parent::getMetadatas($id);
        }
        return $result;
    }

    /**
     * This method will do nothing when constructor failed.
     *
     * {@inheritdoc}
     */
    public function touch($id, $extraLifetime) {
        $result = false;
        if ( $this->works ) {
            $result = parent::touch($id, $extraLifetime);
        }
        return $result;
    }

    /**
     * This method will return all capabilities disabled when constructor failed.
     *
     * {@inheritdoc}
     */
    public function getCapabilities() {
        $result = array(
            'automatic_cleaning' => false,
            'tags'               => false,
            'expired_read'       => false,
            'priority'           => false,
            'infinite_lifetime'  => false,
            'get_list'           => false,
        );
        if ( $this->works ) {
            $result = parent::getCapabilities();
        }
        return $result;
    }

    protected function processRedisException($e, $doing) {
        $message = sprintf( "%s: Caught Redis Exception during '%s'.\n%s",
                            __CLASS__,
                            $doing,
                            (string)$e );
        // We can't use Zend_Log here because it may not be loaded yet.
        error_log( $message );
    }

    protected function getFrontendPrefix() {
        if ( is_null($this->frontendPrefix) ) {
            $this->frontendPrefix = Mage::app()->getCacheInstance()->getFrontend()->getOption('cache_id_prefix');
        }
        return $this->frontendPrefix;
    }

    /**
     * Function to determine if we are currently in admin or cli.
     * This function must work in a very early stage so we can't use Mage::app()
     * @return bool
     */
    public static function isAdmin() {
        static $result = null;
        if ( is_null($result) ) {
            $result = false;
            $baseScript = basename($_SERVER['SCRIPT_FILENAME']);
            if ( 0 === strpos($baseScript,'n98') ||
                 0 === strpos($baseScript,'cron') ||
                 0 === strpos($baseScript,'asyncindex') ) {
                // CLI or Cron
                $result = true;
            } else {
                $adminPath = null;
                $config = Mage::getConfig();
                if ( !empty($config) ) {
                    // Not using Mage_Adminhtml_Helper_Data::XML_PATH_... here because it does not exist in older Magento versions
                    $useCustomAdminPath = (bool)(string)$config->getNode('default/admin/url/use_custom_path');
                    if ($useCustomAdminPath) {
                        $adminPath = (string)$config->getNode('default/admin/url/custom_path');
                    }
                    if ( empty($adminPath) ) {
                        $adminPath = (string)$config->getNode('admin/routers/adminhtml/args/frontName');
                    }
                    $request = new Zend_Controller_Request_Http;
                    $pathParts = explode( '/', trim($request->getPathInfo(),'/') );
                    if ( isset($pathParts[0]) && $pathParts[0] == $adminPath ) {
                        $result = true;
                    }
                }
            }
        }
        return $result;
    }
}
