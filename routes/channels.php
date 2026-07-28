<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. Since we use a public channel for Rekon P1,
| no authorization callbacks are required here.
|
*/

Broadcast::channel('rekon-p1', function () {
    return true;
});
