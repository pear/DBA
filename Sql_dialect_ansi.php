<?php
// define what tokens are accepted by the SQL dialect.
// it is not entirely efficient to have these as a string that we postprocess
// but it's easy to work with for prototyping and only processed once on
// parser creation
$dialect = array(
    'commands'=>'alter create drop select delete insert update',
    'operators'=>'= <> < <= > >= like not is in between',
    'types'=>'character char varchar nchar bit numeric decimal dec integer int smallint float real double date time timestamp interval bool boolean set enum text',
    'conjunctions'=>'by as on into from where with',
    'functions'=>'avg count max min sum',
    'reserved'=> 'absolute action add all allocate and any are asc assertion at authorization begin bit_length both cascade cascaded case cast catalog char_length character_length check close coalesce collate collation column commit connect connection constraint constraints continue convert corresponding cross current current_date current_time current_timestamp current_user cursor day deallocate declare default deferrable deferred desc describe descriptor diagnostics disconnect distinct domain else end end-exec escape except exception exec execute exists external extract false fetch first for foreign found full get global go goto grant group having hour identity immediate indicator initially inner input insensitive intersect isolation join key language last leading left level local lower match minute module month names national natural next no null nullif octet_length of only open option or order outer output overlaps pad partial position precision prepare preserve primary prior privileges procedure public read references relative restrict revoke right rollback rows schema scroll second section session session_user size some space sql sqlcode sqlerror sqlstate substring system_user table temporary then timezone_hour timezone_minute to trailing transaction translate translation trim true union unique unknown upper usage user using value values varying view when whenever work write year zone eoc');

$typeClasses = array(
    'integer'=>  'integer',
    'int'=>      'integer',
    'smallint'=> 'integer',
    'float'=>    'real',
    'real'=>     'real',
    'double'=>   'real',
    'decimal'=>  'real',
    'dec'=>      'real',
    'numeric'=>  'real',
    'decimal'=>  'real',
    'character'=>'char',
    'char'=>     'char',
    'varchar'=>  'char',
    'set'=>      'set',
    'enum'=>     'set',
);

?>
