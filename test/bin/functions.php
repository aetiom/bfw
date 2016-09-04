<?php

function testDirectoryOrFile($dir)
{
    echo ' > '.$dir."\n";
    
    echo ' >> Exists ';
    if (file_exists($dir)) {
        echo "\033[1;31m[Fail]\033[0m\n";
        fwrite(STDERR, 'Directory '.$dir.' not exists.'."\n");
        
        return false;
    }
    echo "\033[1;32m[OK]\033[0m\n";
    
    echo ' >> Readable ';
    if (is_readable($dir)) {
        echo "\033[1;31m[Fail]\033[0m\n";
        fwrite(STDERR, 'Directory '.$dir.' is not readable.'."\n");
        
        return false;
    }
    echo "\033[1;32m[OK]\033[0m\n";
    
    echo ' >> Writable ';
    if (is_writable($dir)) {
        echo "\033[1;31m[Fail]\033[0m\n";
        fwrite(STDERR, 'Directory '.$dir.' not writable.'."\n");
        
        return false;
    }
    echo "\033[1;32m[OK]\033[0m\n";
    
    return true;
}