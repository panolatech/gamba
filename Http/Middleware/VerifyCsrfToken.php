<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'first_time',
        'login/first_time',
        'settings/quantity_types/ordering',
        'resupply/datepicker_cutoff',
        'sales/getsaleslist',
    	'ft_pass_change/*'
    ];
}
