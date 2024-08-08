<?php
/*
Plugin Name: Odoo Sync
Description: Beheer klanten en synchroniseer met Odoo.
Version: 1.0
Author: Your Name
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

// Voeg de klantenlijstpagina toe aan het admin menu
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

// Klant toevoegen/bewerken/verwijderen pagina
function odoo_sync_klant_toevoegen_page() {
    // Hier komt het formulier en de verwerking van het toevoegen van een klant
}

// Synchroniseer klantdata met Odoo via API
function odoo_sync_with_odoo($post_id, $post, $update) {
    if ($post->post_type !== 'klant') {
        return;
    }

    $odoo_url = 'https://odoo.example.com/api/klanten'; // Pas aan naar je Odoo API URL
    $api_key = 'YOUR_API_KEY'; // Vervang door je echte Odoo API-sleutel

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
