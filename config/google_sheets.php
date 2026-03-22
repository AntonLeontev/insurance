<?php

return [

    'enabled' => filter_var(env('GOOGLE_SHEETS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),

    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),

    /**
     * A1 range for values.append (sheet name + columns), e.g. Sheet1!A:J or Лист1!A:J
     */
    'append_range' => env('GOOGLE_SHEETS_APPEND_RANGE', 'Счета!A:J'),

    /**
     * Absolute path to the service account JSON key file.
     */
    'credentials_file_name' => env('GOOGLE_SHEETS_CREDENTIALS_FILE_NAME'),
    'agency_id' => env('GOOGLE_SHEETS_AGENCY_ID'),

];
