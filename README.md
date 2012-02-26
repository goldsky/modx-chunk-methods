# This is exclusively for MODX Revolution.

This methods are helpers to apply snippet's output to chunk or template file.
They are using @BINDINGs like:
    * @CODE / @CODE:
    * @FILE / @FILE:
    * @CHUNK / or empty @BINDING identifier (default)

The snippet's call would be:
    [[SnippetName? &tpl=`@FILE: path/to/file`]]

The snippet's API usage would be:
    
    $object = new YourClass($modx);
    $object->setConfig($scriptProperties);

    // code continues...
    // let's say this as an EXAMPLE
    $contents = $object->getContents();

    $output = $object->parseTpl($scriptProperties['tpl'], $contents);

    return $output;
