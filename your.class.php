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
     */
    public function __construct(modX $modx) {
        $this->modx =& $modx;
    }

    /**
     * Set class configuration exclusively for multiple snippet calls
     * @param   array   $config     snippet's parameters
     */
    public function setConfig(array $config=array()) {
        $this->config = $config;
    }


    /**
     * Parsing template
     * @param   string  $tpl    @BINDINGs options
     * @param   array   $phs    placeholders
     * @return  string  parsed output
     */
    public function parseTpl($tpl, array $phs=array()) {
        $output = '';
        if (preg_match('/^(@CODE|@INLINE)/i', $tpl)) {
            $tplString= preg_replace('/^(@CODE|@INLINE)/i', '', $tpl);
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
            } catch(Exception $e) {
                return $e->getMessage();
            }
        }
        // ignore @CHUNK / @CHUNK: / empty @BINDING
        else {
            $tpl= preg_replace('/^@CHUNK/i', '', $tpl);
            // tricks @CHUNK:
            $tpl = ltrim($tpl, ':');
            $tpl = trim($tpl);

            $chunk = $this->modx->getObject('modChunk', array ('name' => $tpl), true);
            if (empty($chunk)) {
                // try to use @splittingred's fallback
                $f = $this->config['chunksPath'] . strtolower($tpl) . '.chunk.tpl';
                try {
                    $output = $this->parseTplFile($f, $phs);
                } catch(Exception $e) {
                    $output = $e->getMessage();
                    return 'Chunk: ' . $tpl . ' is not found, neither the file ' . $output;
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
    public function parseTplCode($code, array $phs=array()) {
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
    public function parseTplFile($file, array $phs=array()) {
        $chunk = false;
        if (!file_exists($file)) {
            throw new Exception ('File: ' . $file . ' is not found.');
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
            $this->modx->getOption('core_path')
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

