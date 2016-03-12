<?php
/**
 * @package casepress-add_def_res
 * @version 1.0
 */
/*
Plugin Name: casepress-add_def_res
Plugin URI: -
Author: Petro-1
Version: 1.0
*/

$taxname = 't-branche'; //название таксономии, к которой добавляем поля и мету
// Поля при добавлении элемента таксономии
add_action("{$taxname}_add_form_fields", 'add_new_custom_fields');
// Поля при редактировании элемента таксономии
add_action("{$taxname}_edit_form_fields", 'edit_new_custom_fields');
// Сохранение при добавлении элемента таксономии
add_action("create_{$taxname}", 'save_custom_taxonomy_meta');
// Сохранение при редактировании элемента таксономии
add_action("edited_{$taxname}", 'save_custom_taxonomy_meta');

function edit_new_custom_fields( $term ) {
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="cp_def_res_div">Ответственный по умолчанию для поразделения</label></th>
        <td>
            <div style="width: 95%;" id="cp_def_res_div">
                <?php
                CaseViewsAdminSingltone::add_field_responsible($post);
                if ( $cp_defres_id = get_term_meta( $term->term_id, 'cp_def_responsible', 1 ) ) : ?>
                    <script>
                        jQuery(document).ready(function($) {
                            $("#cp_case_responsible_input").select2(
                                "data",
                                <?php echo json_encode(array('id' => $cp_defres_id, 'title' => get_the_title($cp_defres_id))); ?>
                            );
                        });
                    </script>
                <?php endif; ?>
                <span class="description">При связи данного подразделения с делом, будет автоматически доавблен выбранный ответсвенный</span>
            </div>
        </td>
    </tr>
    <?php
}

function add_new_custom_fields( $taxonomy_slug ){
    ?>
    <div style="width: 95%;" class="form-field">
        <?php
        CaseViewsAdminSingltone::add_field_responsible($post);
        ?>
        <script> jQuery('#cp_case_responsible_label').html('Ответственный по умолчанию для поразделения:'); </script>
        <p>При связи данного подразделения с делом, будет автоматически доавблен выбранный ответсвенный</p>
    </div>
    <?php
}

function save_custom_taxonomy_meta( $term_id ) {
    if ( ! isset($_POST['cp_responsible']) )
        return;

    // Все ОК! Теперь, нужно сохранить/удалить данные
    if( empty($_POST['cp_responsible']) ){
        delete_term_meta( $term_id, cp_def_responsible ); // удаляем поле если значение пустое
        return;
    }

    update_term_meta( $term_id, cp_def_responsible, $_POST['cp_responsible'] ); // add_term_meta() работает автоматически
    return $term_id;
}

add_action( 'added_term_relationship', 'cp_add_def_res', 10000, 2);

function cp_add_def_res($object_id, $tt_id) {
    $tt = get_term($tt_id);
    if ($tt->taxonomy == 't-branche') { //Если добавленный термин - Подразделение
        $key = 'responsible-cp-posts-sql';
        if (!get_post_meta($object_id, $key, 1)) { //то смотрим нет ли уже ответственного у дела
            if ($res_id = get_term_meta($tt_id, 'cp_def_responsible' , 1)) {//есть ли ответственный по умолчанию у подразделения
                if (isset($_REQUEST['cp_responsible'])) { // если есть поле выбора персоны, то смотрим, что в нем
                    $data = trim($_REQUEST['cp_responsible']);
                    if (empty($data)) {//если это поле пустое, то доавбялем ответственного по умолчанию
                        update_post_meta($object_id, $key, $res_id);
                        update_option('cp_def_res_added', 1); //сообщаем, что доавблен ответсвенный по умолчанию, чтобы значение не удалялось из-за того что поле ответственный пустое caes_view_admin.php 770
                    }
                } else { // если поля выбора персоны вообще нет, то тоже добавляем ответственного по умолчанию
                    update_post_meta($object_id, $key, $res_id);
                }
            }
        }
    }
}