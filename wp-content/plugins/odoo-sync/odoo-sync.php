<?php
/*
Plugin Name: Odoo Sync
Description: Beheer klanten en synchroniseer met Odoo.
Version: 1.0
Author: Thinh
*/

// Voeg custom post type 'Klanten' toe
function odoo_sync_register_custom_post_type() {
    $args = array(
        'labels' => array(
            'name' => __('Klanten'),
            'singular_name' => __('Klant'),
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'custom-fields'),
    );
    register_post_type('klant', $args);
}
add_action('init', 'odoo_sync_register_custom_post_type');

// Voeg de klantenlijstpagina en klant toevoegen/bewerken pagina toe aan het admin menu
function odoo_sync_add_admin_menu() {
    add_menu_page(
        'Klanten Overzicht',
        'Klanten Overzicht',
        'manage_options',
        'odoo-sync-klanten-overzicht',
        'odoo_sync_klanten_overzicht_page',
        'dashicons-id-alt',
        20
    );

    add_submenu_page(
        'odoo-sync-klanten-overzicht',
        'Klant Toevoegen/Bewerken',
        'Klant Toevoegen',
        'manage_options',
        'odoo-sync-klant-toevoegen',
        'odoo_sync_klant_toevoegen_page'
    );
}
add_action('admin_menu', 'odoo_sync_add_admin_menu');

// Admin pagina voor het klantenoverzicht
function odoo_sync_klanten_overzicht_page() {
    $args = array('post_type' => 'klant', 'posts_per_page' => -1);
    $klanten = new WP_Query($args);

    echo '<div class="wrap"><h1>Klanten Overzicht</h1>';
    echo '<a href="admin.php?page=odoo-sync-klant-toevoegen" class="page-title-action">Klant toevoegen</a>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Naam</th><th>Bewerken</th><th>Verwijderen</th></tr></thead>';
    echo '<tbody>';

    while ($klanten->have_posts()) {
        $klanten->the_post();
        $edit_link = admin_url('post.php?post=' . get_the_ID() . '&action=edit');
        $delete_link = get_delete_post_link(get_the_ID());
        echo '<tr>';
        echo '<td>' . get_the_ID() . '</td>';
        echo '<td>' . get_the_title() . '</td>';
        echo '<td><a href="' . $edit_link . '">Bewerken</a></td>';
        echo '<td><a href="' . $delete_link . '" onclick="return confirm(\'Weet je zeker dat je deze klant wilt verwijderen?\')">Verwijderen</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

// Formulier voor klant toevoegen/bewerken
function odoo_sync_klant_toevoegen_page() {
    $klant_id = isset($_GET['klant_id']) ? intval($_GET['klant_id']) : 0;
    $klant = get_post($klant_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $klant_data = array(
            'post_title' => sanitize_text_field($_POST['klant_naam']),
            'post_content' => sanitize_textarea_field($_POST['klant_omschrijving']),
            'post_type' => 'klant',
            'post_status' => 'publish',
        );

        if ($klant_id > 0) {
            $klant_data['ID'] = $klant_id;
            wp_update_post($klant_data);
        } else {
            $klant_id = wp_insert_post($klant_data);
        }

        // Synchroniseer met Odoo
        odoo_sync_with_odoo($klant_id, (object) $klant_data, true);
        
        echo '<div class="notice notice-success is-dismissible"><p>Klant succesvol opgeslagen en gesynchroniseerd met Odoo.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1><?php echo $klant_id > 0 ? 'Klant Bewerken' : 'Klant Toevoegen'; ?></h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="klant_naam">Naam</label></th>
                    <td><input name="klant_naam" type="text" id="klant_naam" value="<?php echo esc_attr($klant ? $klant->post_title : ''); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="klant_omschrijving">Omschrijving</label></th>
                    <td><textarea name="klant_omschrijving" id="klant_omschrijving" class="large-text" rows="5" required><?php echo esc_textarea($klant ? $klant->post_content : ''); ?></textarea></td>
                </tr>
            </table>
            <p class="submit"><button type="submit" class="button-primary"><?php echo $klant_id > 0 ? 'Klant Bewerken' : 'Klant Toevoegen'; ?></button></p>
        </form>
    </div>
    <?php
}

// Synchroniseer klantdata met Odoo via API
function odoo_sync_with_odoo($post_id, $post, $update) {
    if ($post->post_type !== 'klant') {
        return;
    }

    $odoo_url = ''; // Odoo API URL
    $api_key = ''; // Odoo API-sleutel

    $klant_data = array(
        'id' => $post_id,
        'name' => get_the_title($post_id),
        'description' => $post->post_content,
    );

    $response = wp_remote_post($odoo_url, array(
        'method' => 'POST',
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($klant_data),
    ));

    if (is_wp_error($response)) {
        error_log('Error synchronizing with Odoo: ' . $response->get_error_message());
    } else {
        error_log('Klantdata gesynchroniseerd met Odoo: ' . wp_remote_retrieve_body($response));
    }
}
add_action('save_post', 'odoo_sync_with_odoo', 10, 3);

// Haal klantdata op vanuit Odoo en werk WordPress bij
function odoo_fetch_from_odoo() {
    $odoo_url = ''; // Odoo API URL
    $api_key = ''; // Odoo API key

    $response = wp_remote_get($odoo_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching from Odoo: ' . $response->get_error_message());
        return;
    }

    $odoo_customers = json_decode(wp_remote_retrieve_body($response), true);

    foreach ($odoo_customers as $odoo_customer) {
        $existing_post = get_posts(array(
            'post_type' => 'klant',
            'meta_key' => '_odoo_id',
            'meta_value' => $odoo_customer['id'],
            'numberposts' => 1,
        ));

        $klant_data = array(
            'post_title' => sanitize_text_field($odoo_customer['name']),
            'post_content' => sanitize_textarea_field($odoo_customer['description']),
            'post_type' => 'klant',
            'post_status' => 'publish',
        );

        if ($existing_post) {
            $klant_data['ID'] = $existing_post[0]->ID;
            wp_update_post($klant_data);
        } else {
            $klant_id = wp_insert_post($klant_data);
            update_post_meta($klant_id, '_odoo_id', $odoo_customer['id']);
        }
    }
}

// Eventueel: webhook listener voor real-time updates van Odoo
function odoo_webhook_listener() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $payload = json_decode(file_get_contents('php://input'), true);

    // Verwerk de payload en werk de klantdata bij in WordPress

    http_response_code(200);
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('odoo-sync/v1', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'odoo_webhook_listener',
    ));
});
