<?php

namespace BFW\Helpers;

use \Exception;

/**
 * Helpers to securize data
 */
class Secure
{
    /**
     * @const ERR_SECURE_KNOWN_TYPE_FILTER_NOT_MANAGED Exception code if the
     * data type into the method secureKnownTypes() is not managed.
     */
    const ERR_SECURE_KNOWN_TYPE_FILTER_NOT_MANAGED = 1609001;
    
    /**
     * @const ERR_SECURE_ARRAY_KEY_NOT_EXIST If the asked key not exist into
     * the array to secure.
     */
    const ERR_SECURE_ARRAY_KEY_NOT_EXIST = 1609002;
    
    /**
     * Hash a string
     * 
     * @param string $val String to hash
     * 
     * @return string
     */
    public static function hash(string $val): string
    {
        return hash('sha256', md5($val));
    }

    /**
     * Securize a string for some types with filter_var function.
     * 
     * @param mixed $data String to securize
     * @param string $type Type of filter
     * 
     * @return mixed
     * 
     * @throws \Exception If the type is unknown
     */
    public static function secureKnownType($data, string $type)
    {
        $filterType = 'text';

        if ($type === 'int' || $type === 'integer') {
            $filterType = FILTER_VALIDATE_INT;
        } elseif ($type === 'float' || $type === 'double') {
            $filterType = FILTER_VALIDATE_FLOAT;
        } elseif ($type === 'bool' || $type === 'boolean') {
            $filterType = FILTER_VALIDATE_BOOLEAN;
        } elseif ($type === 'email') {
            $filterType = FILTER_VALIDATE_EMAIL;
        }

        if ($filterType === 'text') {
            throw new Exception(
                'Cannot secure the type',
                self::ERR_SECURE_KNOWN_TYPE_FILTER_NOT_MANAGED
            );
        }

        return filter_var($data, $filterType);
    }
    
    /**
     * Securise a mixed data type who are not managed by securiseKnownType.
     * We work the data like if the type is a string.
     * 
     * @param mixed $data The variable to securise
     * @param boolean $htmlentities If use htmlentities function
     *  to a better security
     * 
     * @return mixed
     */
    public static function secureUnknownType($data, bool $htmlentities)
    {
        $currentClass    = get_called_class();
        $sqlSecureMethod = $currentClass::getSqlSecureMethod();
        
        if ($sqlSecureMethod !== null) {
            $data = $sqlSecureMethod($data);
        } else {
            $data = addslashes($data);
        }

        if ($htmlentities === true) {
            $data = htmlentities($data, ENT_COMPAT | ENT_HTML401);
        }
        
        return $data;
    }

    /**
     * Securise a variable
     * 
     * @param mixed $data The variable to securise
     * @param string $type The type of datas
     * @param boolean $htmlentities If use htmlentities function
     *  to a better security
     * 
     * @return mixed
     * 
     * @throws \Exception If an error with a type of data
     */
    public static function secureData($data, string $type, bool $htmlentities)
    {
        $currentClass = get_called_class();
        
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                unset($data[$key]);

                $key = $currentClass::secureData($key, gettype($key), true);
                $val = $currentClass::secureData($val, $type, $htmlentities);

                $data[$key] = $val;
            }

            return $data;
        }

        try {
            return $currentClass::secureKnownType($data, $type);
        } catch (Exception $ex) {
            if ($ex->getCode() !== self::ERR_SECURE_KNOWN_TYPE_FILTER_NOT_MANAGED) {
                throw new Exception($ex->getMessage(), $ex->getCode());
            }
            //Else : Use securise like if it's a text type
        }

        return $currentClass::secureUnknownType($data, $htmlentities);
    }

    /**
     * Get the sqlSecure function declared in bfw config file
     * 
     * @return null|string
     */
    public static function getSqlSecureMethod()
    {
        $app       = \BFW\Application::getInstance();
        $secureFct = $app->getConfig()->getValue(
            'sqlSecureMethod',
            'global.php'
        );

        if (!is_callable($secureFct, false)) {
            return null;
        }

        return $secureFct;
    }

    /**
     * Securise the value of an array key for a declared type.
     * 
     * @param array &$array The array where is the key
     * @param string $key The key where is the value to securize
     * @param string $type The type of data
     * @param boolean $htmlentities (default: false) If use htmlentities
     *  function to a better security
     * 
     * @return mixed
     * 
     * @throws \Exception If the key not exist in array
     */
    public static function getSecureKeyInArray(
        array &$array,
        string $key,
        string $type,
        bool $htmlentities = false
    ) {
        if (!isset($array[$key])) {
            throw new Exception(
                'The key '.$key.' not exist',
                self::ERR_SECURE_ARRAY_KEY_NOT_EXIST
            );
        }

        $currentClass = get_called_class();
        return $currentClass::secureData(
            trim($array[$key]),
            $type,
            $htmlentities
        );
    }
    
    /**
     * Obtain many key from an array in one time
     * 
     * @param array &$arraySrc The source array
     * @param array $keysList The key list to obtain.
     *  For each item, the key is the name of the key in source array; And the
     *  value the type of the value. The value can also be an object. In this
     *  case, the properties "type" contain the value type, and the "htmlenties"
     *  property contain the boolean who indicate if secure system 
     *  will use htmlentities.
     * @param boolean $throwOnError (defaut true) If a key not exist, throw an
     *  exception. If false, the value will be null into returned array
     * 
     * @return array
     * 
     * @throws \Exception If a key is not found and if $throwOnError is true
     */
    public static function getManySecureKeys(
        array &$arraySrc,
        array $keysList,
        bool $throwOnError = true
    ): array {
        $currentClass = get_called_class();
        $result       = [];
        
        foreach ($keysList as $keyName => $infos) {
            if (!is_array($infos)) {
                $infos = [
                    'type'         => $infos,
                    'htmlentities' => false
                ];
            }
            
            try {
                $result[$keyName] = $currentClass::getSecureKeyInArray(
                    $arraySrc,
                    $keyName,
                    $infos['type'],
                    $infos['htmlentities']
                );
            } catch (Exception $ex) {
                if ($throwOnError === true) {
                    throw new Exception(
                        'Error to obtain the key '.$keyName,
                        self::ERR_SECURE_ARRAY_KEY_NOT_EXIST,
                        $ex
                    );
                } else {
                    $result[$keyName] = null;
                }
            }
        }
        
        return $result;
    }
}
