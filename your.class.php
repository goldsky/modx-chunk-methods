<?php

/**
 * @license     public domain
 * @package     class helper methods
 */
class ClassName {

    public $modx;
    public $config;

    /**
     * constructor
     * @param   modX    $modx
     * @param   array   $config     parameters
     */
    public function __construct(modX $modx, $config = array()) {
        $this->modx = & $modx;
        $config = is_array($config) ? $config : array();
        $basePath = $this->modx->getOption('package.core_path', $config, $this->modx->getOption('core_path') . 'components/package/');
        $assetsUrl = $this->modx->getOption('package.assets_url', $config, $this->modx->getOption('assets_url') . 'components/package/');
        $this->config = array_merge(array(
            'basePath' => $basePath,
            'corePath' => $basePath,
            'modelPath' => $basePath . 'model/',
            'processorsPath' => $basePath . 'processors/',
            'chunksPath' => $basePath . 'elements/chunks/',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'assetsUrl' => $assetsUrl,
            'connectorUrl' => $assetsUrl . 'connector.php',
                ), $config);

        $this->modx->lexicon->load('package:default');
        $this->modx->addPackage('package', $this->config['modelPath']);
    }

    /**
     * Set class configuration exclusively for multiple snippet calls
     * @param   array   $config     snippet's parameters
     */
    public function setConfigs(array $config = array()) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Define individual config for the class
     * @param   string  $key    array's key
     * @param   string  $val    array's value
     */
    public function setConfig($key, $val) {
        $this->config[$key] = $val;
    }

    /**
     * Parsing template
     * @param   string  $tpl    @BINDINGs options
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @link    http://forums.modx.com/thread/74071/help-with-getchunk-and-modx-speed-please?page=2#dis-post-413789
     */
    public function parseTpl($tpl, array $phs = array()) {
        $output = '';
        if (preg_match('/^(@CODE|@INLINE)/i', $tpl)) {
            $tplString = preg_replace('/^(@CODE|@INLINE)/i', '', $tpl);
            // tricks @CODE: / @INLINE:
            $tplString = ltrim($tplString, ':');
            $tplString = trim($tplString);
            $output = $this->parseTplCode($tplString, $phs);
        } elseif (preg_match('/^@FILE/i', $tpl)) {
            $tplFile = preg_replace('/^@FILE/i', '', $tpl);
            // tricks @FILE:
            $tplFile = ltrim($tplFile, ':');
            $tplFile = trim($tplFile);
            $tplFile = $this->replacePropPhs($tplFile);
            try {
                $output = $this->parseTplFile($tplFile, $phs);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        // ignore @CHUNK / @CHUNK: / empty @BINDING
        else {
            $tplChunk = preg_replace('/^@CHUNK/i', '', $tpl);
            // tricks @CHUNK:
            $tplChunk = ltrim($tpl, ':');
            $tplChunk = trim($tpl);

            $chunk = $this->modx->getObject('modChunk', array('name' => $tplChunk), true);
            if (empty($chunk)) {
                // try to use @splittingred's fallback
                $f = $this->config['chunksPath'] . strtolower($tplChunk) . '.chunk.tpl';
                try {
                    $output = $this->parseTplFile($f, $phs);
                } catch (Exception $e) {
                    $output = $e->getMessage();
                    return 'Chunk: ' . $tplChunk . ' is not found, neither the file ' . $output;
                }
            } else {
                $output = $this->modx->getChunk($tpl, $phs);
            }
        }

        return $output;
    }

    /**
     * Parsing inline template code
     * @param   string  $code   HTML with tags
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     */
    public function parseTplCode($code, array $phs = array()) {
        $chunk = $this->modx->newObject('modChunk');
        $chunk->setContent($code);
        $chunk->setCacheable(false);
        $phs = $this->replacePropPhs($phs);
        return $chunk->process($phs);
    }

    /**
     * Parsing file based template
     * @param   string  $file   file path
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     * @throws  Exception if file is not found
     */
    public function parseTplFile($file, array $phs = array()) {
        if (!file_exists($file)) {
            throw new Exception('File: ' . $file . ' is not found.');
        }
        $o = file_get_contents($file);
        $chunk = $this->modx->newObject('modChunk');

        // just to create a name for the modChunk object.
        $name = strtolower(basename($file));
        $name = rtrim($name, '.tpl');
        $name = rtrim($name, '.chunk');
        $chunk->set('name', $name);

        $chunk->setCacheable(false);
        $chunk->setContent($o);
        $output = $chunk->process($phs);

        return $output;
    }

    /**
     * Replace the property's placeholders
     * @param   string|array    $subject    Property
     * @return  array           The replaced results
     */
    public function replacePropPhs($subject) {
        $pattern = array(
            '/\{core_path\}/',
            '/\{base_path\}/',
            '/\{assets_url\}/',
            '/\{filemanager_path\}/',
            '/\[\[\+\+core_path\]\]/',
            '/\[\[\+\+base_path\]\]/'
        );
        $replacement = array(
            $this->modx->getOption('core_path'),
            $this->modx->getOption('base_path'),
            $this->modx->getOption('assets_url'),
            $this->modx->getOption('filemanager_path'),
            $this->modx->getOption('core_path'),
            $this->modx->getOption('base_path')
        );
        if (is_array($subject)) {
            $parsedString = array();
            foreach ($subject as $k => $s) {
                if (is_array($s)) {
                    $s = $this->replacePropPhs($s);
                }
                $parsedString[$k] = preg_replace($pattern, $replacement, $s);
            }
            return $parsedString;
        } else {
            return preg_replace($pattern, $replacement, $subject);
        }
    }

}