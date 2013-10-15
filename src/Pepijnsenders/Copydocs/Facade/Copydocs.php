<?php

namespace Pepijnsenders\Copydocs\Facade;

use Illuminate\Support\Facades\Facade as Facade;

class Copydocs extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'oauth'; }

}