<?php
/**
 * Created by PhpStorm.
 * User: hazelcodes
 * Date: 7/3/23
 * Time: 4:36 PM
 */

namespace Hazelveek\PhpWitAi\Facades;

use Hazelveek\PhpWitAi\WitClient;
use Illuminate\Support\Facades\Facade;

class WitAi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return WitClient::class;
    }

    /**
     * Get the meaning of a sentence.
     *
     * @param string $sentence
     * @return array
     */
    public static function getTextMeaning($sentence)
    {
        return static::getFacadeRoot()->message($sentence);
    }

    /**
     * Get the meaning of a sentence.git
     *
     * @param string $sentence
     * @return array
     */
    public static function getSpeechMeaning($audioFilePath)
    {
        return static::getFacadeRoot()->speech($audioFilePath);
    }

}