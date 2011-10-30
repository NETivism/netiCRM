<?php
if($argv[4]){
  define("CIVICRM_CONFDIR", rtrim($argv[4], '/'));
}

ini_set( 'include_path', '.' . PATH_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'packages' . PATH_SEPARATOR . '..' );
ini_set( 'memory_limit', '512M' );

$versionFile = "version.xml";
$versionXML  =& parseInput( $versionFile );
$db_version  = $versionXML->version_no;
$build_version = preg_replace('/^(\d{1,2}\.\d{1,2})\.(\d{1,2}|\w{4,7})$/i', '$1', $db_version);
if ( isset($argv[2]) ) {
    // change the version to that explicitly passed, if any 
    $db_version = $argv[2];
}
if ($build_version < 1.1) {
    echo "The Database is not compatible for this version";
    exit();
}

if ( substr( phpversion( ), 0, 1 ) != 5 ) {
    echo phpversion( ) . ', ' . substr( phpversion( ), 0, 1 ) . "\n";
    echo "
CiviCRM requires a PHP Version >= 5
Please upgrade your php / webserver configuration
Alternatively you can get a version of CiviCRM that matches your PHP version
";
    exit( );
}

// default cms is 'drupal', if not specified 
$cms = isset($argv[3]) ? strtolower($argv[3]) : 'drupal';
if ( !in_array($cms, array('drupal', 'standalone', 'joomla')) ) {
    echo "Config file for '{$cms}' not known.";
    exit();
} else if ( $cms !== 'joomla' ) {
    copy("../{$cms}/civicrm.config.php.{$cms}", '../civicrm.config.php');
}

require_once 'Smarty/Smarty.class.php';
require_once 'PHP/Beautifier.php';

require_once '../civicrm.config.php';

require_once 'CRM/Core/Config.php';
require_once 'CRM/Core/I18n.php';
require_once 'CRM/Utils/Tree.php';

function createDir( $dir, $perm = 0755 ) {
    if ( ! is_dir( $dir ) ) {
        mkdir( $dir, $perm, true );
    }
}

$smarty = new Smarty( );
$smarty->template_dir = './templates';
$smarty->plugins_dir  = array( '../packages/Smarty/plugins', '../CRM/Core/Smarty/plugins' );

 if ( isset ( $_SERVER['TMPDIR'] ) ) {
     $tempDir = $_SERVER['TMPDIR'];
 } else {
     $tempDir = '/tmp';
 }

$compileDir = $tempDir . '/templates_c';

if (file_exists($compileDir)) {
    $oldTemplates = preg_grep('/tpl\.php$/', scandir($compileDir));
    foreach ($oldTemplates as $templateFile) {
        unlink($compileDir . '/' . $templateFile);
    }
}
$smarty->compile_dir = $compileDir;

createDir( $smarty->compile_dir );

$smarty->clear_all_cache();

if ( isset( $argv[1] ) && ! empty( $argv[1] ) ) {
    $file = $argv[1];
} else {
    $file = 'schema/Schema.xml';
}

$sqlCodePath = '../sql/';
$phpCodePath = '../';
$tplCodePath = '../templates/';

echo "Parsing input file $file\n";
$dbXML = parseInput( $file );
//print_r( $dbXML );

echo "Extracting database information\n";
$database =& getDatabase( $dbXML );
// print_r( $database );

$classNames = array( );

echo "Extracting table information\n";
$tables   =& getTables( $dbXML, $database );
resolveForeignKeys( $tables, $classNames );
$tables = orderTables( $tables );

//echo "\n\n\n\n\n*****************************************************************************\n\n";
//print_r(array_keys($tables));
//exit(1);

echo "Generating tests truncate file\n";

$truncate = '<?xml version="1.0" encoding="UTF-8" ?>
<!--  Truncate all tables that will be used in the tests  -->
<dataset>';
$tbls = array_keys($tables);
foreach( $tbls as $d => $t ) {
    $truncate = $truncate . "\n  <$t />\n";
}

$truncate = $truncate . "</dataset>\n";
$ft = fopen( $sqlCodePath . "../tests/phpunit/CiviTest/truncate.xml", "w" );
fputs( $ft, $truncate );
fclose( $ft );
unset( $ft );
unset( $truncate );

$smarty->assign_by_ref( 'database', $database );
$smarty->assign_by_ref( 'tables'  , $tables   );
$tmpArray = array_keys( $tables );
$tmpArray = array_reverse( $tmpArray );
$smarty->assign_by_ref( 'dropOrder', $tmpArray );
$smarty->assign( 'mysql', 'modern' );

echo "Generating sql file\n";
$sql = $smarty->fetch( 'schema.tpl' );

createDir( $sqlCodePath );
$fd = fopen( $sqlCodePath . "civicrm.mysql", "w" );
fputs( $fd, $sql );
fclose($fd);

echo "Generating sql drop tables file\n";
$sql = $smarty->fetch( 'drop.tpl' );

createDir( $sqlCodePath );
$fd = fopen( $sqlCodePath . "civicrm_drop.mysql", "w" );
fputs( $fd, $sql );
fclose($fd);

echo "Generating navigation file\n";
$fd  = fopen( $sqlCodePath . "civicrm_navigation.mysql", "w" );
$sql = $smarty->fetch( 'civicrm_navigation.tpl' );
fputs( $fd, $sql );
fclose($fd);

// write the civicrm data file
// and translate the {ts}-tagged strings
$smarty->clear_all_assign();
$smarty->assign('build_version',$build_version);

$config =& CRM_Core_Config::singleton(false);
$locales = array( );
if (substr($config->gettextResourceDir, 0, 1) === '/') {
    $localeDir = $config->gettextResourceDir;
} else {
    $localeDir = '../' . $config->gettextResourceDir;
}
if (file_exists($localeDir)) {
    $config->gettextResourceDir = $localeDir;
    $locales = preg_grep('/^[a-z][a-z]_[A-Z][A-Z]$/', scandir($localeDir));
}
if (!in_array('en_US', $locales)) array_unshift($locales, 'en_US');

// CRM-5308 / CRM-3507 - we need {localize} to work in the templates
require_once 'CRM/Core/Smarty/plugins/block.localize.php';
$smarty->register_block('localize', 'smarty_block_localize');

global $tsLocale;
foreach ($locales as $locale) {
    echo "Generating data files for $locale\n";
    $tsLocale = $locale;
    $smarty->assign('locale', $locale);

    $data   = array();
    $data[] = $smarty->fetch('civicrm_country.tpl');
    $data[] = $smarty->fetch('civicrm_state_province.tpl');
    $data[] = $smarty->fetch('civicrm_currency.tpl');
    $data[] = $smarty->fetch('civicrm_data.tpl');
    $data[] = $smarty->fetch('civicrm_navigation.tpl');

    $data[] = " UPDATE civicrm_domain SET version = '$db_version';";

    $data = implode("\n", $data);

    // write the initialize base-data sql script
    $filename = 'civicrm_data';
    if ($locale != 'en_US') $filename .= ".$locale";
    $filename .= '.mysql';
    $fd = fopen( $sqlCodePath . $filename, "w" );
    fputs( $fd, $data );
    fclose( $fd );

    // write the acl sql script
    $data = $smarty->fetch('civicrm_acl.tpl');

    $filename = 'civicrm_acl';
    if ($locale != 'en_US') $filename .= ".$locale";
    $filename .= '.mysql';
    $fd = fopen( $sqlCodePath . $filename, "w" );
    fputs( $fd, $data );
    fclose( $fd );
}
echo "\ncivicrm_domain.version := $db_version\n\n";

$tsLocale = 'en_US';

$sample  = $smarty->fetch('civicrm_sample.tpl');
$sample .= $smarty->fetch('civicrm_acl.tpl');
file_put_contents($sqlCodePath . 'civicrm_sample.mysql', $sample);


$beautifier = new PHP_Beautifier(); // create a instance
$beautifier->addFilter('ArrayNested');
$beautifier->addFilter('Pear'); // add one or more filters
$beautifier->addFilter('NewLines', array( 'after' => 'class, public, require, comment' ) ); // add one or more filters
$beautifier->setIndentChar(' ');
$beautifier->setIndentNumber(4);
$beautifier->setNewLine("\n");

foreach ( array_keys( $tables ) as $name ) {
    $smarty->clear_all_cache();
    echo "Generating $name as " . $tables[$name]['fileName'] . "\n";
    $smarty->clear_all_assign( );

    $smarty->assign_by_ref( 'table', $tables[$name] );
    $php = $smarty->fetch( 'dao.tpl' );

    $beautifier->setInputString( $php );
    
    if ( empty( $tables[$name]['base'] ) ) {
        echo "No base defined for $name, skipping output generation\n";
        continue;
    }

    $directory = $phpCodePath . $tables[$name]['base'];
    createDir( $directory );
    $beautifier->setOutputFile( $directory . $tables[$name]['fileName'] );
    $beautifier->process(); // required
    
    $beautifier->save( );
}

echo "Generating CRM_Core_I18n_SchemaStructure...\n";
$columns = array();
$indices = array();
foreach ($tables as $table) {
    if ($table['localizable']) {
        $columns[$table['name']] = array();
    } else {
        continue;
    }
    foreach ($table['fields'] as $field) {
        if ($field['localizable']) $columns[$table['name']][$field['name']] = $field['sqlType'];
    }
    if (isset($table['index'])) {
        foreach ($table['index'] as $index) {
            if ($index['localizable']) $indices[$table['name']][$index['name']] = $index;
        }
    }
}

$smarty->clear_all_cache();
$smarty->clear_all_assign();
$smarty->assign_by_ref('columns', $columns);
$smarty->assign_by_ref('indices', $indices);

$beautifier->setInputString($smarty->fetch('schema_structure.tpl'));
$beautifier->setOutputFile("$phpCodePath/CRM/Core/I18n/SchemaStructure.php");
$beautifier->process();
$beautifier->save();

// add the Subversion revision to templates
// use svnversion if the version was not specified explicitely on the commandline
if (isset($argv[2]) and $argv[2] != '') {
    $svnversion = $argv[2];
} else {
//    $svnversion = `svnversion .`;
    $gitversion = `git describe`;
    $gitversion = explode('-', $gitversion);
    $hash = array_pop($gitversion);
    $revision_num = array_pop($gitversion);
    $svnversion = 'r'.$revision_num;
}
file_put_contents("$tplCodePath/CRM/common/version.tpl", $svnversion);

echo "Generating civicrm-version file\n";
$smarty->assign('db_version',$db_version);
$smarty->assign('cms',ucwords($cms));
$fd  = fopen( $phpCodePath . "civicrm-version.txt", "w" );
$sql = $smarty->fetch( 'civicrm_version.tpl' );
fputs( $fd, $sql );
fclose($fd);

// unlink the templates_c directory
foreach(glob($tempDir . '/templates_c/*') as $tempFile) {
  unlink($tempFile);
}
rmdir($tempDir . '/templates_c');

function &parseInput( $file ) {
    $dom = new DomDocument( );
    $dom->load( $file );
    $dom->xinclude( );
    $dbXML = simplexml_import_dom( $dom );
    return $dbXML;
}

function &getDatabase( &$dbXML ) {
    $database = array( 'name' => trim( (string ) $dbXML->name ) );

    $attributes = '';
    checkAndAppend( $attributes, $dbXML, 'character_set', 'DEFAULT CHARACTER SET ', '' );
    checkAndAppend( $attributes, $dbXML, 'collate', 'COLLATE ', '' );
    $database['attributes'] = $attributes;

    
    $tableAttributes_modern = $tableAttributes_simple = '';
    checkAndAppend( $tableAttributes_modern, $dbXML, 'table_type', 'ENGINE=', '' );
    checkAndAppend( $tableAttributes_simple, $dbXML, 'table_type', 'TYPE=', '' );
    $database['tableAttributes_modern'] = trim( $tableAttributes_modern . ' ' . $attributes );
    $database['tableAttributes_simple'] = trim( $tableAttributes_simple );

    $database['comment'] = value( 'comment', $dbXML, '' );

    return $database;
}

function &getTables( &$dbXML, &$database ) {
    global $build_version ;
    $tables = array();
    foreach ( $dbXML->tables as $tablesXML ) {
        foreach ( $tablesXML->table as $tableXML ) {
            if ( value( 'drop', $tableXML, 0 ) > 0 and value( 'drop', $tableXML, 0 ) <= $build_version) {
                continue;
            }

            if ( value( 'add', $tableXML, 0 ) <= $build_version) {
                getTable( $tableXML, $database, $tables );
            }
        }
    }

    return $tables;
}

function resolveForeignKeys( &$tables, &$classNames ) {
    foreach ( array_keys( $tables ) as $name ) {
        resolveForeignKey( $tables, $classNames, $name );
    }
}

function resolveForeignKey( &$tables, &$classNames, $name ) {
    if ( ! array_key_exists( 'foreignKey', $tables[$name] ) ) {
        return;
    }
    
    foreach ( array_keys( $tables[$name]['foreignKey'] ) as $fkey ) {
        $ftable = $tables[$name]['foreignKey'][$fkey]['table'];
        if ( ! array_key_exists( $ftable, $classNames ) ) {
            echo "$ftable is not a valid foreign key table in $name";
            continue;
        }
        $tables[$name]['foreignKey'][$fkey]['className'] = $classNames[$ftable];
        $tables[$name]['foreignKey'][$fkey]['fileName']  = str_replace( '_', '/', $classNames[$ftable] ) . '.php';
        $tables[$name]['fields'][$fkey]['FKClassName' ] = $classNames[$ftable];
    }
    
}

function orderTables( &$tables ) {
    $ordered = array( );

    while ( ! empty( $tables ) ) {
        foreach ( array_keys( $tables ) as $name ) {
            if ( validTable( $tables, $ordered, $name ) ) {
                $ordered[$name] = $tables[$name];
                unset( $tables[$name] );
            }
        }
    }
    return $ordered;

}

function validTable( &$tables, &$valid, $name ) {
    if ( ! array_key_exists( 'foreignKey', $tables[$name] ) ) {
        return true;
    }

    foreach ( array_keys( $tables[$name]['foreignKey'] ) as $fkey ) {
        $ftable = $tables[$name]['foreignKey'][$fkey]['table'];
        if ( ! array_key_exists( $ftable, $valid ) && $ftable !== $name ) {
            return false;
        }
    }
    return true;
}

function getTable( $tableXML, &$database, &$tables ) {
    global $classNames;
    global $build_version ;
    $name  = trim((string ) $tableXML->name );
    $klass = trim((string ) $tableXML->class );
    $base  = value( 'base', $tableXML ) . '/DAO/';
    $pre   = str_replace( '/', '_', $base );
    $classNames[$name]  = $pre . $klass;

    $localizable = false;
    foreach ($tableXML->field as $fieldXML) {
        if ($fieldXML->localizable) {
            $localizable = true;
            break;
        }
    }

    $table = array( 'name'       => $name,
                    'base'       => $base,
                    'fileName'   => $klass . '.php',
                    'objectName' => $klass,
                    'labelName'  => substr($name, 8),
                    'className'  => $classNames[$name],
                    'attributes_simple' => trim($database['tableAttributes_simple']),
                    'attributes_modern' => trim($database['tableAttributes_modern']),
                    'comment'    => value( 'comment', $tableXML ),
                    'localizable'=> $localizable,
                    'log'        => value( 'log', $tableXML, 'false' ) );
    
    $config  =& CRM_Core_Config::singleton(false);
    $fields  = array( );
    foreach ( $tableXML->field as $fieldXML ) {
        if ( value( 'drop', $fieldXML, 0 ) > 0 and value( 'drop', $fieldXML, 0 ) <= $build_version) {
            continue;
        }
        
        if ( value( 'add', $fieldXML, 0 ) <= $build_version) {
            getField( $fieldXML, $fields );
        }
    }

    $table['fields' ] =& $fields;
    // print_r($table['fields' ]);
    //Anil
    $table['hasEnum'] = false;
    foreach ($table['fields'] as $field) {
        if ($field['crmType'] == 'CRM_Utils_Type::T_ENUM') {
            $table['hasEnum'] = true;
            break;
        }
    }

    if ( value( 'primaryKey', $tableXML ) ) {
        getPrimaryKey( $tableXML->primaryKey, $fields, $table );
    }

    $config  =& CRM_Core_Config::singleton(false);
    if ( value( 'index', $tableXML ) ) {
        $index   = array( );
        foreach ( $tableXML->index as $indexXML ) {
            if ( value( 'drop', $indexXML, 0 ) > 0 and value( 'drop', $indexXML, 0 ) <= $build_version) { 
                continue; 
            } 

            getIndex( $indexXML, $fields, $index );
        }
        $table['index' ] =& $index;
    }

    if ( value( 'foreignKey', $tableXML ) ) {
        $foreign   = array( );
        foreach ( $tableXML->foreignKey as $foreignXML ) {
            // print_r($foreignXML);
            
            if ( value( 'drop', $foreignXML, 0 ) > 0 and value( 'drop', $foreignXML, 0 ) <= $build_version) {
                continue;
            }
            if ( value( 'add', $foreignXML, 0 ) <= $build_version) {
                getForeignKey( $foreignXML, $fields, $foreign, $name );
            }
            
        }
        $table['foreignKey' ] =& $foreign;
    }

    $tables[$name] =& $table;
    return;
}

function getField( &$fieldXML, &$fields ) {
    $name  = trim( (string ) $fieldXML->name );
    $field = array( 'name' => $name, 'localizable' => $fieldXML->localizable );
    $type = (string ) $fieldXML->type;
    switch ( $type ) {
    case 'varchar':
        $field['sqlType'] = 'varchar(' . (int ) $fieldXML->length . ')';
        $field['phpType'] = 'string';
        $field['crmType'] = 'CRM_Utils_Type::T_STRING';
        $field['length' ] = (int ) $fieldXML->length;
        $field['size'   ] = getSize($field['length']);
        break;

    case 'char':
        $field['sqlType'] = 'char(' . (int ) $fieldXML->length . ')';
        $field['phpType'] = 'string';
        $field['crmType'] = 'CRM_Utils_Type::T_STRING';
        $field['length' ] = (int ) $fieldXML->length;
        $field['size'   ] = getSize($field['length']);
        break;

    case 'enum':
        $value = (string ) $fieldXML->values;
        $field['sqlType'] = 'enum(';
        $field['values']  = array( );
        $field['enumValues'] = $value;
        $values = explode( ',', $value );
        $first = true;
        foreach ( $values as $v ) {
            $v = trim($v);
            $field['values'][]  = $v;

            if ( ! $first ) {
                $field['sqlType'] .= ', ';
            }
            $first = false;
            $field['sqlType'] .= "'$v'";
        }
        $field['sqlType'] .= ')';
        $field['phpType'] = $field['sqlType'];
        $field['crmType'] = 'CRM_Utils_Type::T_ENUM';
        break;

    case 'text':
        $field['sqlType'] = $field['phpType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper( $type );
        $field['rows']    = value( 'rows', $fieldXML );
        $field['cols']    = value( 'cols', $fieldXML );
        break;

    case 'datetime':
        $field['sqlType'] = $field['phpType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME';
        break;

    case 'boolean':
        // need this case since some versions of mysql do not have boolean as a valid column type and hence it
        // is changed to tinyint. hopefully after 2 yrs this case can be removed.
        $field['sqlType'] = 'tinyint';
        $field['phpType'] = $type;
        $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper($type);
        break;

    case 'decimal':
        $field['sqlType'] = 'decimal(20,2)';
        $field['phpType'] = 'float';
        $field['crmType'] = 'CRM_Utils_Type::T_MONEY';
        break;

    case 'float':
        $field['sqlType'] = 'double';
        $field['phpType'] = 'float';
        $field['crmType'] = 'CRM_Utils_Type::T_FLOAT';
        break;
        
    default:
        $field['sqlType'] = $field['phpType'] = $type;
        if ( $type == 'int unsigned' ) {
            $field['crmType'] = 'CRM_Utils_Type::T_INT';
        } else {
            $field['crmType'] = 'CRM_Utils_Type::T_' . strtoupper( $type );
        }
        
        break;
    }

    $field['required'] = value( 'required', $fieldXML );
    $field['comment' ] = value( 'comment' , $fieldXML );
    $field['default' ] = value( 'default' , $fieldXML );
    $field['import'  ] = value( 'import'  , $fieldXML );
    if( value( 'export'  , $fieldXML )) {
        $field['export'  ]= value( 'export'  , $fieldXML );
    } else {
        $field['export'  ]= value( 'import'  , $fieldXML );
    }
    $field['rule'    ] = value( 'rule'    , $fieldXML );
    $field['title'   ] = value( 'title'   , $fieldXML );
    if ( ! $field['title'] ) {
        $field['title'] = composeTitle( $name );
    }
    $field['headerPattern'] = value( 'headerPattern', $fieldXML );
    $field['dataPattern'] = value( 'dataPattern', $fieldXML );
    $field['uniqueName'] = value( 'uniqueName', $fieldXML );

    $fields[$name] =& $field;
}

function composeTitle( $name ) {
    $names = explode( '_', strtolower($name) );
    $title = '';
    for ( $i = 0; $i < count($names); $i++ ) {
        if ( $names[$i] === 'id' || $names[$i] === 'is' ) {
            // id's do not get titles
            return null;
        }

        if ( $names[$i] === 'im' ) {
            $names[$i] = 'IM';
        } else {
            $names[$i] = ucfirst( trim($names[$i]) );
        }

        $title = $title . ' ' . $names[$i];
    }
    return trim($title);
}

function getPrimaryKey( &$primaryXML, &$fields, &$table ) {
    $name = trim( (string ) $primaryXML->name );
    
    /** need to make sure there is a field of type name */
    if ( ! array_key_exists( $name, $fields ) ) {
        echo "primary key $name does not have a  field definition, ignoring\n";
        return;
    }

    // set the autoincrement property of the field
    $auto = value( 'autoincrement', $primaryXML );
    $fields[$name]['autoincrement'] = $auto;
    $primaryKey = array( 'name'          => $name,
                         'autoincrement' => $auto );
    $table['primaryKey'] =& $primaryKey;
}

function getIndex(&$indexXML, &$fields, &$indices)
{
    //echo "\n\n*******************************************************\n";
    //echo "entering getIndex\n";

    $index = array();
    $indexName = trim((string)$indexXML->name);   // empty index name is fine
    $index['name'] = $indexName;
    $index['field'] = array();

    // populate fields
    foreach ($indexXML->fieldName as $v) {
        $fieldName = (string)($v);
	$length = (string)($v['length']);
	if (strlen($length) > 0) {
	  $fieldName = "$fieldName($length)";
	}
        $index['field'][] = $fieldName;
    }

    $index['localizable'] = false;
    foreach ($index['field'] as $fieldName) {
        if (isset($fields[$fieldName]) and $fields[$fieldName]['localizable']) {
            $index['localizable'] = true;
            break;
        }
    }

    // check for unique index
    if (value('unique', $indexXML)) {
        $index['unique'] = true;
    }

    //echo "\$index = \n";
    //print_r($index);

    // field array cannot be empty
    if (empty($index['field'])) {
        echo "No fields defined for index $indexName\n";
        return;
    }

    // all fieldnames have to be defined and should exist in schema.
    foreach ($index['field'] as $fieldName) {
        if (!$fieldName) {
            echo "Invalid field defination for index $indexName\n";
            return;
        }
	$parenOffset = strpos($fieldName,'(');
	if ($parenOffset > 0) {
	  $fieldName = substr($fieldName,0,$parenOffset);
	}
        if (!array_key_exists($fieldName, $fields)) {
            echo "Table does not contain $fieldName\n";
            print_r( $fields );
            exit( );
            return;
        }
    }
    $indices[$indexName] =& $index;
}


function getForeignKey( &$foreignXML, &$fields, &$foreignKeys, &$currentTableName ) {
    $name = trim( (string ) $foreignXML->name );
    
    /** need to make sure there is a field of type name */
    if ( ! array_key_exists( $name, $fields ) ) {
        echo "foreign $name does not have a field definition, ignoring\n";
        return;
    }

    /** need to check for existence of table and key **/
    global $classNames;
    $table = trim( value( 'table' , $foreignXML ) );
    $foreignKey = array( 'name'       => $name,
                         'table'      => $table,
                         'uniqName'   => "FK_{$currentTableName}_{$name}",
                         'key'        => trim( value( 'key'   , $foreignXML ) ),
                         'import'     => value( 'import', $foreignXML, false ),
                         'export'     => value( 'import', $foreignXML, false ),
                         'className'  => null, // we do this matching in a seperate phase (resolveForeignKeys)
                         'onDelete'   => value('onDelete', $foreignXML, false),
                         );
    $foreignKeys[$name] =& $foreignKey;
}

function value( $key, &$object, $default = null ) {
    if ( isset( $object->$key ) ) {
        return (string ) $object->$key;
    }
    return $default;
}

function checkAndAppend( &$attributes, &$object, $name, $pre = null, $post = null ) {
    if ( ! isset( $object->$name ) ) {
        return;
    }

    $value = $pre . trim($object->$name) . $post;
    append( $attributes, ' ', trim($value) );
        
}

function append( &$str, $delim, $name ) {
    if ( empty( $name ) ) {
        return;
    }

    if ( is_array( $name ) ) {
        foreach ( $name as $n ) {
            if ( empty( $n ) ) {
                continue;
            }
            if ( empty( $str ) ) {
                $str = $n;
            } else {
                $str .= $delim . $n;
            }
        }
    } else {
        if ( empty( $str ) ) {
            $str = $name;
        } else {
            $str .= $delim . $name;
        }
    }
}

/**
 * four
 * eight
 * twelve
 * sixteen
 * medium (20)
 * big (30)
 * huge (45)
 */

function getSize( $maxLength ) {
    if ( $maxLength <= 2 ) {
        return 'CRM_Utils_Type::TWO';
    } 
    if ( $maxLength <= 4 ) {
        return 'CRM_Utils_Type::FOUR';
    } 
    if ( $maxLength <= 8 ) {
        return 'CRM_Utils_Type::EIGHT';
    } 
    if ( $maxLength <= 16 ) {
        return 'CRM_Utils_Type::TWELVE';
    } 
    if ( $maxLength <= 32 ) {
        return 'CRM_Utils_Type::MEDIUM';
    } 
    if ( $maxLength <= 64 ) {
        return 'CRM_Utils_Type::BIG';
    } 
    return 'CRM_Utils_Type::HUGE';
}



