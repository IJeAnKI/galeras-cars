<?php
// Local database configuration
$LOCAL_HOST = 'localhost';
$LOCAL_DBNAME = 'app_beta';
$LOCAL_USERNAME = 'postgres';
$LOCAL_PASSWORD = '1010139077_Jccr';
$LOCAL_PORT = '5432';

// Supabase database configuration
$SUPA_HOST = 'aws-1-us-east-1.pooler.supabase.com';
$SUPA_DBNAME = 'postgres';
$SUPA_USERNAME = 'postgres.mkuinjvlmafazmebyuun';
$SUPA_PASSWORD = 'unicesmag@@';
$SUPA_PORT = '6543';

// Conexión local
$local_conn = pg_connect("host=$LOCAL_HOST port=$LOCAL_PORT dbname=$LOCAL_DBNAME user=$LOCAL_USERNAME password=$LOCAL_PASSWORD");

if(!$local_conn){
    error_log("Error: Unable to connect to local database: " . pg_last_error());
}

// Conexión Supabase
$supa_conn = @pg_connect("host=$SUPA_HOST port=$SUPA_PORT dbname=$SUPA_DBNAME user=$SUPA_USERNAME password=$SUPA_PASSWORD");

if(!$supa_conn){
    error_log("Error: Unable to connect to Supabase database");
}
?>