<?php
require_once 'DBA_Table.php';

function quickform_dba_add(&$form, $schema, $auxMeta)
{
    foreach ($schema as $name=>$meta) {
        if (isset($auxMeta['default'])) {
            $defaults[$name] = $auxMeta['default'];
        }
        if (isset($auxMeta[$name])) {
            $desc = isset($auxMeta['desc']) ? $auxMeta['desc'] : $name;
            switch($meta[DBA_TYPE]) {
                case DBA_INTEGER:
                    if (isset($auxMeta[$name]['min']) &&
                        isset($auxMeta[$name]['max'])) {
                        $form->addElement('select', $name, $desc,
                                          range($auxMeta[$name]['min'],
                                          $auxMeta[$name]['max']));
                    } else {
                        $form->addElement('text', $name, $desc,
                                          array('size'=>4, 'maxlength'=>4));
                    }
                    break;
                case DBA_VARCHAR:
                    $form->addElement('text', $name, $desc,
                                      array('size'=>$meta['size']));
                    break;
                case DBA_BOOLEAN:
                    $form->addElement('select', $name, $desc,
                                      array('yes'=>'Yes', 'no'=>'No'));
                    break;
                case DBA_TEXT:
                    $form->addElement('textarea', $name, $desc,
                                      array('rows'=>4, 'wrap'=>'soft', 'cols'=>45));
                    break;
            }
        }
    }
}

function quickform_dba_post(&$form, $schema, $auxMeta)
{
    foreach ($schema as $name=>$meta) {
        if ($isset($auxMeta[$name]) && isset($_POST[$name])) {
            if (($meta[DBA_TYPE] == DBA_INTEGER) &&
                isset($auxMeta['min'])) {
                $data[$name] = $_POST[$name] - $auxMeta['min'];
            } else {
                $data[$name] = $_POST[$name];
            }
        }
    }
    return $data;
}
?>
