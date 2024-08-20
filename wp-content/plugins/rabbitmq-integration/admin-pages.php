<?php
/**
 * Admin page content for 'Klanten Overzicht'.
 */
function rsc_customer_overview_page() {
    ?>
    <div class="wrap">
        <h1>Klanten Overzicht</h1>
        <div id="rsc-customer-list">
            <?php rsc_display_customers(); ?>
        </div>
        <div id="rsc-edit-form" style="display: none;">
            <h2>Bewerk Klant</h2>
            <form id="rsc-edit-customer-form" method="post">
                <?php wp_nonce_field('rsc_save_customer_action', 'rsc_save_customer_nonce'); ?>
                <input type="hidden" id="rsc-customer-id" name="rsc-customer-id">
                <label for="rsc-customer-name">Naam:</label>
                <input type="text" id="rsc-customer-name" name="rsc-customer-name" required>
                <label for="rsc-customer-email">E-mail:</label>
                <input type="email" id="rsc-customer-email" name="rsc-customer-email" required>
                <input type="submit" value="Opslaan Bewerking">
                <button type="button" id="rsc-cancel-edit">Annuleer</button>
            </form>
        </div>
        <script>
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('rsc-edit-button')) {
                    var id = e.target.getAttribute('data-id');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=rsc_get_customer&id=' + id)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('rsc-customer-id').value = data.id;
                            document.getElementById('rsc-customer-name').value = data.name;
                            document.getElementById('rsc-customer-email').value = data.email;
                            document.getElementById('rsc-edit-form').style.display = 'block';
                        });
                } else if (e.target && e.target.classList.contains('rsc-delete-button')) {
                    if (confirm('Weet je zeker dat je deze klant wilt verwijderen?')) {
                        var id = e.target.getAttribute('data-id');
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=rsc_delete_customer&id=' + id)
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    alert('Klant succesvol verwijderd!');
                                    location.reload();
                                } else {
                                    alert('Fout bij het verwijderen van klant');
                                }
                            });
                    }
                }
            });

            document.getElementById('rsc-cancel-edit').addEventListener('click', function() {
                document.getElementById('rsc-edit-form').style.display = 'none';
            });

            document.getElementById('rsc-edit-customer-form').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent the default form submission

                var formData = new FormData(this);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=rsc_save_customer', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert(result.message);
                        location.reload(); // Reload the page to reflect changes
                    } else {
                        alert(result.message);
                    }
                });
            });
        </script>
    </div>
    <?php
}

/**
 * Content for the 'Nieuwe Klant' submenu page.
 */
function rsc_add_customer_page() {
    ?>
    <div class="wrap">
        <h1>Voeg Nieuwe Klant Toe</h1>
        <form id="rsc-add-customer-form" method="post">
            <?php wp_nonce_field('rsc_save_customer_action', 'rsc_save_customer_nonce'); ?>
            <input type="hidden" id="rsc-customer-id" name="rsc-customer-id">
            <label for="rsc-customer-name">Naam:</label>
            <input type="text" id="rsc-customer-name" name="rsc-customer-name" required>
            <label for="rsc-customer-email">E-mail:</label>
            <input type="email" id="rsc-customer-email" name="rsc-customer-email" required>
            <input type="submit" value="Opslaan Klant">
        </form>
        <div id="rsc-message" style="display: none;"></div>
        <script>
            document.getElementById('rsc-add-customer-form').addEventListener('submit', function(e) {
                e.preventDefault();
                var form = this;
                var data = new FormData(form);
                fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=rsc_save_customer', {
                    method: 'POST',
                    body: data
                }).then(response => response.json()).then(result => {
                    var messageDiv = document.getElementById('rsc-message');
                    if (result.success) {
                        messageDiv.textContent = 'Klant succesvol toegevoegd!';
                        messageDiv.style.color = 'green';
                        messageDiv.style.display = 'block';
                        form.reset();
                    } else {
                        messageDiv.textContent = 'Fout bij het toevoegen van klant';
                        messageDiv.style.color = 'red';
                        messageDiv.style.display = 'block';
                    }
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                });
            });
        </script>
    </div>
    <?php
}

function rsc_add_product_page() {
    ?>
    <div class="wrap">
        <h1>Nieuwe Product</h1>
        <form id="rsc-product-form">
            <?php wp_nonce_field('rsc_save_product_action', 'rsc_save_product_nonce'); ?>

            <input type="hidden" id="rsc-product-id" name="rsc-product-id" value="0" />

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="rsc-product-name">Name</label></th>
                    <td><input name="rsc-product-name" id="rsc-product-name" type="text" class="regular-text" required /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="rsc-product-list-price">List Price</label></th>
                    <td><input name="rsc-product-list-price" id="rsc-product-list-price" type="number" step="0.01" class="regular-text" required /></td>
                </tr>
            </table>

            <p class="submit">
                <button type="button" id="rsc-save-product" class="button button-primary">Save Product</button>
            </p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('#rsc-save-product').click(function() {
                var formData = $('#rsc-product-form').serialize();
                $.post(ajaxurl, formData + '&action=rsc_save_product', function(response) {
                    alert(response.message);
                }, 'json');
            });
        });
        </script>
    </div>
    <?php
}




function rsc_product_list_page() {
    global $wpdb;
    $table_name = "{$wpdb->prefix}rsc_products";

    // Retrieve products
    $products = $wpdb->get_results("SELECT * FROM $table_name");

    if (is_wp_error($products)) {
        echo '<p>Error fetching products: ' . $products->get_error_message() . '</p>';
        return;
    }

    ?>
    <div class="wrap">
        <h1>Product Lijst</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>List Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)) : ?>
                    <tr>
                        <td colspan="4">No products found.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($products as $product) : ?>
                        <tr>
                            <td><?php echo esc_html($product->id); ?></td>
                            <td><?php echo esc_html($product->name); ?></td>
                            <td><?php echo esc_html($product->list_price); ?></td>
                            <td>
                                <button class="button rsc-edit-product" data-id="<?php echo esc_attr($product->id); ?>">Bewerk</button>
                                <button class="button rsc-delete-product" data-id="<?php echo esc_attr($product->id); ?>">Verwijder</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div id="rsc-edit-form" style="display:none;">
            <h2>Edit Product</h2>
            <form id="rsc-product-edit-form">
                <?php wp_nonce_field('rsc_save_product_action', 'rsc_save_product_nonce'); ?>

                <input type="hidden" id="rsc-edit-product-id" name="rsc-product-id" value="0" />

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="rsc-edit-product-name">Name</label></th>
                        <td><input name="rsc-edit-product-name" id="rsc-edit-product-name" type="text" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="rsc-edit-product-list-price">List Price</label></th>
                        <td><input name="rsc-edit-product-list-price" id="rsc-edit-product-list-price" type="number" step="0.01" class="regular-text" required /></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="rsc-save-edited-product" class="button button-primary">Save Changes</button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('.rsc-edit-product').click(function() {
                var productId = $(this).data('id');
                $.get(ajaxurl, { action: 'rsc_get_product', id: productId, _wpnonce: '<?php echo wp_create_nonce('rsc_get_product_action'); ?>' }, function(response) {
                    if (response.success) {
                        $('#rsc-edit-product-id').val(response.data.id);
                        $('#rsc-edit-product-name').val(response.data.name);
                        $('#rsc-edit-product-list-price').val(response.data.list_price);
                        $('#rsc-edit-form').show();
                    } else {
                        alert(response.message);
                    }
                }, 'json');
            });

            $('#rsc-save-edited-product').click(function() {
                var formData = $('#rsc-product-edit-form').serialize();
                $.post(ajaxurl, formData + '&action=rsc_save_product', function(response) {
                    alert(response.message);
                    if (response.success) {
                        location.reload();
                    }
                }, 'json');
            });

            $('.rsc-delete-product').click(function() {
                var productId = $(this).data('id');
                if (confirm('Are you sure you want to delete this product?')) {
                    $.post(ajaxurl, {
                        action: 'rsc_delete_product',
                        id: productId,
                        rsc_delete_product_nonce: '<?php echo wp_create_nonce('rsc_delete_product_action'); ?>'
                    }, function(response) {
                        alert(response.message);
                        if (response.success) {
                            location.reload();
                        }
                    }, 'json');
                }
            });
        });
        </script>
    </div>
    <?php
}


?>
