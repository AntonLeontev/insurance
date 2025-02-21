<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('snapshot:create')->daily();
Schedule::command('snapshot:cleanup --keep=7')->daily();
