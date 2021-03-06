<?php
if($DebugMode && $errorRender)
{
    function exception_handler($exception)
    {
        //trigger_error($exception->getMessage(), E_USER_WARNING);
        displayPHPError(
            'Fatal', 
            $exception->getMessage(), 
            $exception->getFile(), 
            $exception->getLine(), 
            $exception->getTrace()
        );
    }
    
    set_exception_handler('exception_handler');
        
    set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line)
    {
        /*
        http://fr2.php.net/manual/fr/function.set-error-handler.php#113567
       v 1        E_ERROR (integer)             Fatal run-time errors. These indicate errors that can not be recovered from, such as a memory allocation problem. Execution of the script is halted.     
       v 2        E_WARNING (integer)           Run-time warnings (non-fatal errors). Execution of the script is not halted.     
       v 4        E_PARSE (integer)             Compile-time parse errors. Parse errors should only be generated by the parser.     
       v 8        E_NOTICE (integer)            Run-time notices. Indicate that the script encountered something that could indicate an error, but could also happen in the normal course of running a script.     
       v 16       E_CORE_ERROR (integer)        Fatal errors that occur during PHP's initial startup. This is like an E_ERROR, except it is generated by the core of PHP.     
       v 32       E_CORE_WARNING (integer)      Warnings (non-fatal errors) that occur during PHP's initial startup. This is like an E_WARNING, except it is generated by the core of PHP.     
       v 64       E_COMPILE_ERROR (integer)     Fatal compile-time errors. This is like an E_ERROR, except it is generated by the Zend Scripting Engine.     
       v 128      E_COMPILE_WARNING (integer)   Compile-time warnings (non-fatal errors). This is like an E_WARNING, except it is generated by the Zend Scripting Engine.     
       v 256      E_USER_ERROR (integer)        User-generated error message. This is like an E_ERROR, except it is generated in PHP code by using the PHP function trigger_error().     
       v 512      E_USER_WARNING (integer)      User-generated warning message. This is like an E_WARNING, except it is generated in PHP code by using the PHP function trigger_error().     
       v 1024     E_USER_NOTICE (integer)       User-generated notice message. This is like an E_NOTICE, except it is generated in PHP code by using the PHP function trigger_error().     
       v 2048     E_STRICT (integer)            Enable to have PHP suggest changes to your code which will ensure the best interoperability and forward compatibility of your code.    Since PHP 5 but not included in E_ALL until PHP 5.4.0
       v 4096     E_RECOVERABLE_ERROR (integer) Catchable fatal error. It indicates that a probably dangerous error occurred, but did not leave the Engine in an unstable state. If the error is not caught by a user defined handle (see also set_error_handler()), the application aborts as it was an E_ERROR.    Since PHP 5.2.0
       v 8192     E_DEPRECATED (integer)        Run-time notices. Enable this to receive warnings about code that will not work in future versions.    Since PHP 5.3.0
       v 16384    E_USER_DEPRECATED (integer)   User-generated warning message. This is like an E_DEPRECATED, except it is generated in PHP code by using the PHP function trigger_error().    Since PHP 5.3.0
       / 32767    E_ALL (integer)               All errors and warnings, as supported, except of level E_STRICT prior to PHP 5.4.0.     32767 in PHP 5.4.x, 30719 in PHP 5.3.x, 6143 in PHP 5.2.x, 2047 previously
        */
        
        if(
            $err_severity == E_ERROR || 
            $err_severity == E_CORE_ERROR || 
            $err_severity == E_USER_ERROR || 
            $err_severity == E_COMPILE_ERROR || 
            $err_severity == E_RECOVERABLE_ERROR
        )
        {$erreurType = 'Fatal';}
        
        elseif(
            $err_severity == E_WARNING || 
            $err_severity == E_CORE_WARNING || 
            $err_severity == E_USER_WARNING || 
            $err_severity == E_COMPILE_WARNING
        )
        {$erreurType = 'Fatal';}
        
        elseif($err_severity == E_PARSE) {$erreurType = 'Parse';}
        elseif($err_severity == E_NOTICE || $err_severity == E_USER_NOTICE) {$erreurType = 'Notice';}
        elseif($err_severity == E_STRICT) {$erreurType = 'Strict';}
        elseif($err_severity == E_RECOVERABLE_ERROR) {$erreurType = '/';}
        elseif($err_severity == E_DEPRECATED || $err_severity == E_USER_DEPRECATED) {$erreurType = 'Deprecated';}
        else {$erreurType = 'Unknow';}
        
        global $errorRender;
        $errorRender($erreurType, $err_msg, $err_file, $err_line, debug_backtrace());
    });
        
    /**
     * @param string $erreurType
     */
    function displayPHPError($erreurType, $err_msg, $err_file, $err_line, $backtrace)
    {
        ob_clean();
        echo '
        <!doctype html>
        <html lang="fr">
            <head>
                <title>Une erreur est parmi nous !</title>
                <style>
                    html {padding:0; margin:0; background-color:#e3e3e3; font-family:sans-serif; font-size: 1em; word-wrap:break-word;}
                    div {position:relative; margin:auto; width:950px; border: 1px solid #a6c9e2; top: 30px; margin-bottom:10px;}
                    p {padding:0; margin:0;}
                    p.title {font-size:1.2em; background-color:#D0DCE9; padding:10px;}
                    p.info {padding:5px; margin-top:10px; margin-bottom:10px;}
                    fieldset {border:none; background-color: white;}
                    pre {width:910px; line-height:1.5;}
                </style>
            </head>
            <body>
                <div>
                    <p class="title">Niarf, une erreur s\'est produite</p>
                    <p class="info">'.$erreurType.' Error : <strong>'.$err_msg.'</strong> in '.$err_file.' at line '.$err_line.'</p>
                    <fieldset><pre>';
                        foreach($backtrace as $i => $info)
                        {
                            echo '#'.$i.'  '.$info['function'];
                            
                            if(isset($info['args']) && count($info['args']) > 0)
                            {
                                echo '(';
                                
                                foreach($info['args'] as $iArgs => $args)
                                {
                                    if($iArgs > 0) {echo ', ';}
                                    
                                        if(is_array($args) || is_object($args)) {echo gettype($args);}
                                    elseif(is_null($args)) {echo 'null';}
                                    else {echo htmlentities($args);}
                                }
                                
                                echo ')';
                            }
                            
                            if(isset($info['file'], $info['line']))
                            {
                                echo ' called at ['.$info['file'].' line '.$info['line'].']';
                            }
                            echo "\n\n";
                        }
                    echo '</pre></fieldset>
                </div>
            <body>
        </html>
        ';
        
        ob_flush();
        exit;
    }
}
