<?php

use App\MidiaMax\Providers\MidiaMaxServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\PassportServiceProvider;

return [
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    PassportServiceProvider::class,
    MidiaMaxServiceProvider::class, // MidiaMax: sempre por ultimo (ver CUSTOMIZATIONS.md)
];
