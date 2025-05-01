<?php
/*
Plugin Name: Link Replacer
Description: جایگزینی لینک‌های قدیمی با لینک جدید در همه نوشته‌ها، صفحات و فیلدهای ACF.
Version: 1.1
Author: jayarsiech
Author URI: https://instagram.com/jayarsiech
*/

add_action('admin_menu', 'link_replacer_menu');

function link_replacer_menu() {
    add_menu_page('تغییر لینک', 'تغییر لینک', 'manage_options', 'link-replacer', 'link_replacer_page');
}

function link_replacer_page() {
    global $wpdb;

    if (isset($_POST['old_link'], $_POST['new_link'])) {
        $old_link = esc_url_raw(trim($_POST['old_link']));
        $new_link = esc_url_raw(trim($_POST['new_link']));

        // 1. جایگزینی در post_content
        $posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_content LIKE '%" . esc_sql($old_link) . "%'");
        $count_content = 0;

        foreach ($posts as $post) {
            $updated_content = str_replace($old_link, $new_link, $post->post_content);
            if ($updated_content !== $post->post_content) {
                wp_update_post([
                    'ID' => $post->ID,
                    'post_content' => $updated_content
                ]);
                $count_content++;
            }
        }

        // 2. جایگزینی در فیلدهای ACF (postmeta)
        $meta_rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT meta_id, post_id, meta_key, meta_value 
                 FROM $wpdb->postmeta 
                 WHERE meta_value LIKE %s",
                '%' . $wpdb->esc_like($old_link) . '%'
            )
        );

        $count_meta = 0;
        foreach ($meta_rows as $row) {
            $new_value = str_replace($old_link, $new_link, $row->meta_value);
            if ($new_value !== $row->meta_value) {
                update_post_meta($row->post_id, $row->meta_key, $new_value);
                $count_meta++;
            }
        }

        echo '<div class="updated"><p>';
        echo '✅ تعداد <strong>' . $count_content . '</strong> نوشته به‌روزرسانی شد.<br>';
        echo '✅ تعداد <strong>' . $count_meta . '</strong> فیلد ACF (postmeta) هم به‌روزرسانی شد.';
        echo '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>🔗 جایگزینی لینک‌ها</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="old_link">لینک قدیمی:</label></th>
                    <td><input type="url" name="old_link" required style="width:100%"></td>
                </tr>
                <tr>
                    <th><label for="new_link">لینک جدید:</label></th>
                    <td><input type="url" name="new_link" required style="width:100%"></td>
                </tr>
            </table>
            <?php submit_button('جایگزین کن'); ?>
        </form>
    </div>
    <?php
}
