<?php

return [
    'grace_days' => (int) env('SUBSCRIPTION_GRACE_DAYS', 30),
    'purge_warning_days' => (int) env('SUBSCRIPTION_PURGE_WARNING_DAYS', 7),
];
