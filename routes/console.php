<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:backup')->hourly();
