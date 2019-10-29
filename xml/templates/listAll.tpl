<?php
// (GenCodeChecksum:{$genCodeChecksum})

return array(
{foreach from=$tables key=tableName item=table}
  '{$table.className}' => array(
    'name' => '{$table.objectName}',
    'class' => '{$table.className}',
    'table' => '{$tableName}',
  ),
{/foreach}
);
