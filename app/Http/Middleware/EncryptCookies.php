<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as BaseEncrypter;

class EncryptCookies extends BaseEncrypter
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [];

    public function isDisabled($name)
    {
        if ($name === slugify(config('app.name')).'_activeWorkspace') {
            return true;
        }
        return parent::isDisabled($name);
    }
}
