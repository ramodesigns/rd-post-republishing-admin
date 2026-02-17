<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

?>

<div class="wrap rd-licensed-domains">

    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <div class="rd-add-domain-card">
        <h2>Add a Licensed Domain</h2>
        <p>Enter the domain name you want to license. An activation key will be generated automatically.</p>
        <div class="rd-add-domain-form">
            <input
                type="text"
                id="rd-domain-input"
                placeholder="e.g. example.com"
                class="regular-text"
                autocomplete="off"
            />
            <button id="rd-add-domain-btn" class="button button-primary">Add Domain</button>
        </div>
        <div id="rd-add-domain-message" class="rd-notice" style="display:none;"></div>
    </div>

    <div class="rd-domains-list-card">
        <h2>Your Licensed Domains</h2>

        <div id="rd-domains-loading" class="rd-loading">
            <span class="spinner is-active"></span>
            Loading your domains&hellip;
        </div>

        <table id="rd-domains-table" class="wp-list-table widefat fixed striped rd-domains-table" style="display:none;">
            <thead>
                <tr>
                    <th scope="col" class="col-domain">Domain</th>
                    <th scope="col" class="col-added">Date Added</th>
                    <th scope="col" class="col-key">Activation Key</th>
                    <th scope="col" class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody id="rd-domains-tbody"></tbody>
        </table>

        <div id="rd-no-domains" class="rd-empty-state" style="display:none;">
            <p>No licensed domains found. Add your first domain above.</p>
        </div>
    </div>

</div>
