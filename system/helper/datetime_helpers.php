<?php

namespace DTHelpers;
use Carbon;

function now() {
    return Carbon\Carbon::now()->toDateTimeString();
}