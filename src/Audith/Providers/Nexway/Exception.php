<?php
namespace Audith\Providers\Nexway;

/**
 * @author      Shahriyar Imanov <shehi@imanov.me>
 */

class Exception extends \Exception
{
    public static $exceptionCodeMapping = array(
        70 => "SecretIsMissingException",
        71 => "SecretNotValidException"
    );
}
