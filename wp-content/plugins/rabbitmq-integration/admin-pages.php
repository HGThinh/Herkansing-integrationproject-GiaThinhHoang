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
?>
