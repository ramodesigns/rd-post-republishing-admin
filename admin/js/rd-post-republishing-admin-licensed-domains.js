(function( $ ) {
	'use strict';

	var restUrl = rdLicensedDomains.restUrl;
	var nonce   = rdLicensedDomains.nonce;

	// ─── Fetch & Render ──────────────────────────────────────────────────────────

	function fetchDomains() {
		$( '#rd-domains-loading' ).show();
		$( '#rd-domains-table' ).hide();
		$( '#rd-no-domains' ).hide();

		$.ajax( {
			url: restUrl + '/list',
			method: 'GET',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', nonce );
			},
			success: function( response ) {
				$( '#rd-domains-loading' ).hide();
				if ( response.success && response.data.length > 0 ) {
					renderDomains( response.data );
					$( '#rd-domains-table' ).show();
				} else {
					$( '#rd-no-domains' ).show();
				}
			},
			error: function() {
				$( '#rd-domains-loading' ).hide();
				$( '#rd-no-domains' ).text( 'Failed to load domains. Please refresh the page.' ).show();
			}
		} );
	}

	function renderDomains( domains ) {
		var $tbody = $( '#rd-domains-tbody' );
		$tbody.empty();
		$.each( domains, function( i, domain ) {
			$tbody.append( buildRow( domain ) );
		} );
	}

	function buildRow( domain ) {
		var keyWrapperId = 'rd-key-wrap-' + domain.id;
		var keyValue     = $( '<span>' ).text( domain.activation_key ).html();

		var $keyCell = $( '<td class="col-key">' ).html(
			'<div id="' + keyWrapperId + '" class="rd-key-wrapper">' +
				'<span class="rd-key-masked">••••••••••••••••••••</span>' +
				'<code class="rd-key-value" style="display:none;">' + keyValue + '</code>' +
			'</div>' +
			'<button class="button button-small rd-toggle-key-btn" data-id="' + domain.id + '" data-showing="0">' +
				'View Key' +
			'</button>'
		);

		var $actionsCell = $( '<td class="col-actions">' ).html(
			'<button class="button button-small button-link-delete rd-delete-btn" data-id="' + domain.id + '">' +
				'Delete' +
			'</button>'
		);

		return $( '<tr>' )
			.attr( 'data-id', domain.id )
			.append( $( '<td class="col-domain">' ).text( domain.domain_name ) )
			.append( $( '<td class="col-added">' ).text( domain.timestamp ) )
			.append( $keyCell )
			.append( $actionsCell );
	}

	// ─── Add Domain ──────────────────────────────────────────────────────────────

	function addDomain() {
		var domainName = $( '#rd-domain-input' ).val().trim();

		if ( ! domainName ) {
			showMessage( 'Please enter a domain name.', 'error' );
			return;
		}

		var $btn = $( '#rd-add-domain-btn' );
		$btn.prop( 'disabled', true ).text( 'Adding…' );

		$.ajax( {
			url: restUrl + '/create',
			method: 'POST',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', nonce );
			},
			data: { domain_name: domainName },
			success: function( response ) {
				$btn.prop( 'disabled', false ).text( 'Add Domain' );
				if ( response.success ) {
					$( '#rd-domain-input' ).val( '' );
					showMessage( 'Domain added successfully!', 'success' );
					fetchDomains();
				} else {
					showMessage( response.message || 'Failed to add domain.', 'error' );
				}
			},
			error: function( xhr ) {
				$btn.prop( 'disabled', false ).text( 'Add Domain' );
				var msg = xhr.responseJSON && xhr.responseJSON.message
					? xhr.responseJSON.message
					: 'Failed to add domain. Please try again.';
				showMessage( msg, 'error' );
			}
		} );
	}

	// ─── Delete Domain ───────────────────────────────────────────────────────────

	function deleteDomain( id ) {
		if ( ! window.confirm( 'Are you sure you want to delete this domain? This cannot be undone.' ) ) {
			return;
		}

		var $row = $( 'tr[data-id="' + id + '"]' );
		$row.find( '.rd-delete-btn' ).prop( 'disabled', true ).text( 'Deleting…' );

		$.ajax( {
			url: restUrl + '/clear',
			method: 'DELETE',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', nonce );
			},
			data: { id: id },
			success: function( response ) {
				if ( response.success ) {
					$row.fadeOut( 250, function() {
						$( this ).remove();
						if ( $( '#rd-domains-tbody tr' ).length === 0 ) {
							$( '#rd-domains-table' ).hide();
							$( '#rd-no-domains' ).show();
						}
					} );
				} else {
					$row.find( '.rd-delete-btn' ).prop( 'disabled', false ).text( 'Delete' );
					window.alert( response.message || 'Failed to delete domain.' );
				}
			},
			error: function( xhr ) {
				$row.find( '.rd-delete-btn' ).prop( 'disabled', false ).text( 'Delete' );
				var msg = xhr.responseJSON && xhr.responseJSON.message
					? xhr.responseJSON.message
					: 'Failed to delete domain. Please try again.';
				window.alert( msg );
			}
		} );
	}

	// ─── Toggle Activation Key ───────────────────────────────────────────────────

	function toggleKey( $btn ) {
		var id       = $btn.data( 'id' );
		var showing  = $btn.data( 'showing' ) === 1;
		var $wrap    = $( '#rd-key-wrap-' + id );

		if ( showing ) {
			$wrap.find( '.rd-key-masked' ).show();
			$wrap.find( '.rd-key-value' ).hide();
			$btn.text( 'View Key' ).data( 'showing', 0 );
		} else {
			$wrap.find( '.rd-key-masked' ).hide();
			$wrap.find( '.rd-key-value' ).show();
			$btn.text( 'Hide Key' ).data( 'showing', 1 );
		}
	}

	// ─── Notifications ───────────────────────────────────────────────────────────

	function showMessage( message, type ) {
		var $el = $( '#rd-add-domain-message' );
		$el.removeClass( 'rd-notice-success rd-notice-error' )
		   .addClass( type === 'success' ? 'rd-notice-success' : 'rd-notice-error' )
		   .text( message )
		   .stop( true, true )
		   .show();

		setTimeout( function() {
			$el.fadeOut( 400 );
		}, 4000 );
	}

	// ─── Boot ────────────────────────────────────────────────────────────────────

	$( document ).ready( function() {
		fetchDomains();

		$( '#rd-add-domain-btn' ).on( 'click', addDomain );

		$( '#rd-domain-input' ).on( 'keypress', function( e ) {
			if ( e.which === 13 ) {
				addDomain();
			}
		} );

		$( document ).on( 'click', '.rd-delete-btn', function() {
			deleteDomain( $( this ).data( 'id' ) );
		} );

		$( document ).on( 'click', '.rd-toggle-key-btn', function() {
			toggleKey( $( this ) );
		} );
	} );

})( jQuery );
