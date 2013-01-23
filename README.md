# This is exclusively for MODX Revolution.

This methods are helpers to apply snippet's output to chunk or template file.
They are using @BINDINGs like:

    * @CODE / @CODE:
    * (or) @INLINE / @INLINE:
    * @FILE / @FILE:
    * @CHUNK / or empty @BINDING identifier (default)

The snippet's call would be:

    [[SnippetName? &tpl=`@CODE: <p>[[+placeholder]]</p>`]]
    [[SnippetName? &tpl=`@FILE: [[++core_path]]path/to/file`]]
    [[SnippetName? &tpl=`chunkName`]]

The snippet's API usage would be:

    $object = new ClassName($modx);
    $object->setConfigs($scriptProperties);
    /**
    * The common usage is applicable like this:
    * $object = new ClassName($modx, $scriptProperties);
    * but fails for multiple snippet calls on the same page.
    * $scriptProperties does not change after the first call.
    */

    // code continues...
    // let's say this as an EXAMPLE
    $contents = $object->getContents();

    $output = $object->parseTpl($scriptProperties['tpl'], $contents);

    return $output;
