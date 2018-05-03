<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Max Hops
    |--------------------------------------------------------------------------
    |
    | This value represents the maximum number of hops that are allowed where
    | hops "[i]ndicates how many vertex hops are necessary for the path; it is
    | zero for direct edges".
    |
    | The more hops that are allowed (and used), then the more DAG edges will
    | be created.  This will have an increasing impact on performance, space,
    | and memory.  Whether or not it's negligible, noticeable, or impactful
    | depends on a variety of factors.
    */

    'max_hops' => 5,
];
