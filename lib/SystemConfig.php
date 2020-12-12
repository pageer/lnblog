<?php

class SystemConfig {
    const DEFAULT_USERDATA_NAME = 'userdata';
    const PATH_CONFIG_NAME = 'pathconfig.php';

    private $globals;
    private $fs;

    private $path_config = [];

    private static $single_instance;

    public function __construct(FS $fs = null, GlobalFunctions $globals = null) {
        $this->globals = $globals ?: new GlobalFunctions();
        $this->fs = $fs ?: new NativeFS();
    }

    public static function instance() {
        if (!self::$single_instance) {
            self::$single_instance = new self();
        }
        return self::$single_instance;
    }

    # Method: installRoot
    # Gets or sets the path and URL of the system install root directory.
    #
    # Parameters:
    # path - UrlPath representing the path and URL
    #
    # Returns:
    # UrlPath containing the path and URL
    public function installRoot(UrlPath $path = null): UrlPath {
        $this->loadPathConfig();
        if ($path) {
            $this->path_config['INSTALL_ROOT'] = $path;
        }
        return $this->path_config['INSTALL_ROOT'] ?? new UrlPath(dirname(__DIR__), '');
    }

    # Method: userData
    # Gets or sets the path and URL of the system userdata directory.
    #
    # Parameters:
    # path - UrlPath representing the path and URL
    #
    # Returns:
    # UrlPath containing the path and URL
    public function userData(UrlPath $path = null): UrlPath {
        $this->loadPathConfig();
        if ($path) {
            $this->path_config['USER_DATA'] = $path;
        }
        return $this->path_config['USER_DATA'] ?? $this->getDefaultUserdataPath();
    }

    # Method: blogRegistry
    # Gets the mapping of registered blogIDs to url and path.
    #
    # Returns:
    # Array mapping blogIDs to  UrlPath objects containing the path and URL
    public function blogRegistry(): array {
        $this->loadPathConfig();
        return $this->path_config['BLOGS'] ?? [];
    }

    # Method: registerBlog
    # Register a blog ID, path, and URL with the system
    #
    # Parameters:
    # blogid - string continaing the blog identifier
    # path   - UrlPath with the path and URL of the blog
    public function registerBlog(string $blogid, UrlPath $path) {
        $this->loadPathConfig();
        $this->path_config['BLOGS'][$blogid] = $path;
    }

    # Method: unregisterBlog
    # Remove the registration for a blog.
    #
    # Parameters:
    # blogid - string continaing the blog identifier
    public function unregisterBlog(string $blogid) {
        $this->loadPathConfig();
        unset($this->path_config['BLOGS'][$blogid]);
    }

    # Method: writeConfig
    # Writes out the configuration file used to record the URLs and paths.
    public function writeConfig() {
        $this->loadPathConfig();
        $output = var_export($this->path_config, true);
        $output = "<?php\nreturn $output;\n";
        $result = $this->fs->write_file($this->getPathConfigFile(), $output);
        if (!$result) {
            throw new FileWriteFailed(spf_("Could not write %s", self::PATH_CONFIG_NAME));
        }
    }

    # Method: configExists
    # Checks if a path config has been written yet.
    #
    # Returns:
    # True if the install-wide pathconfig.php exists, false otherwise.
    public function configExists() {
        return $this->fs->file_exists($this->getPathConfigFile());
    }

    # Method: definePathConstants
    # Defines the INSTALL_ROOT, INSTALL_ROOT_URL, USER_DATA_PATH, and optionally 
    # BLOG_ROOT and BLOG_ROOT_URL legacy constants.  These constants are deprecared,
    # but are still used by a lot of existing code, and so aren't going away yet.
    public function definePathConstants(string $blog_dir = '') {
        $this->loadPathConfig();
        if (!defined("INSTALL_ROOT")) {
            define("INSTALL_ROOT", $this->installRoot()->path());
        }
        if (!defined("INSTALL_ROOT_URL")) {
            define("INSTALL_ROOT_URL", $this->installRoot()->url());
        }
        if (!defined("USER_DATA_PATH")) {
            define("USER_DATA_PATH", $this->userData()->path());
        }
        if ($blog_dir && !defined("BLOG_ROOT")) {
            $blog_path = $this->fs->realpath($blog_dir);
            foreach ($this->blogRegistry() as $urlpath) {
                if ($this->fs->realpath($urlpath->path()) == $blog_path) {
                    define("BLOG_ROOT", $urlpath->path());
                    define("BLOG_ROOT_URL", $urlpath->url());
                }
            }
        }
    }

    private function loadPathConfig() {
        if (!$this->path_config) {
            $this->path_config = @$this->globals->include($this->getPathConfigFile()) ?: [];
        }
    }
    
    private function getDefaultUserdataPath(): UrlPath {
        return new UrlPath(Path::mk(dirname($this->installRoot()->path()), self::DEFAULT_USERDATA_NAME), '');
    }

    private function getPathConfigFile(): string {
        return Path::mk(__DIR__, '..', self::PATH_CONFIG_NAME);
    }
}
