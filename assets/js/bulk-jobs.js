/**
 * Init User Engine – background job progress (Top-up / Send Notification).
 *
 * Polls admin-ajax while a job is queued or running and refreshes the rows of
 * the "Background Jobs" table. Polling is read only: jobs run from WP-Cron
 * (built-in, or a system cron job on sites that set DISABLE_WP_CRON).
 *
 * Only when the admin clicks "Process from this page" on a stalled job does
 * this page ask the server to process the queue, a few seconds per request,
 * for as long as the tab stays open.
 *
 * @since 1.7.1
 */
( function () {
	'use strict';

	var cfg = window.InitPluginSuiteUserEngineBulkJobs || {};

	// Confirm before cancelling (rows are re-rendered, so listen on the document).
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;

		if ( form && form.classList && form.classList.contains( 'iue-bulk-job-cancel' ) ) {
			if ( ! window.confirm( form.getAttribute( 'data-confirm' ) || '' ) ) {
				e.preventDefault();
			}
		}
	} );

	var table = document.querySelector( 'table.iue-bulk-jobs' );

	if ( ! table || ! cfg.ajaxUrl ) {
		return;
	}

	var tbody    = table.querySelector( 'tbody' );
	var kind     = table.getAttribute( 'data-kind' ) || 'topup';
	var interval = parseInt( cfg.interval, 10 ) || 4000;
	var failures = 0;
	var manual   = false;
	var timer    = null;
	var busy     = false;

	function schedule( delay ) {
		window.clearTimeout( timer );
		timer = window.setTimeout( tick, delay );
	}

	function tick() {
		if ( busy ) {
			return;
		}

		busy = true;

		var body = new window.FormData();
		body.append( 'action', manual ? 'iue_bulk_jobs_run' : 'iue_bulk_jobs_status' );
		body.append( 'nonce', cfg.nonce || '' );
		body.append( 'kind', kind );

		window.fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) {
				return res.json();
			} )
			.then( function ( json ) {
				busy = false;

				if ( ! json || ! json.success || ! json.data ) {
					throw new Error( 'Bad response' );
				}

				failures = 0;

				// Server-rendered, escaped markup.
				tbody.innerHTML = json.data.html;

				if ( manual ) {
					markManualButtons();
				}

				if ( json.data.active ) {
					// Manual processing: start the next slice right away.
					schedule( manual ? 300 : interval );
				} else {
					manual = false;
					table.setAttribute( 'data-active', '0' );
				}
			} )
			.catch( function () {
				busy = false;
				failures++;

				// Back off on errors, give up after a while (reloading the page starts again).
				if ( failures < 10 ) {
					schedule( interval * ( failures + 1 ) );
				} else {
					manual = false;
				}
			} );
	}

	function markManualButtons() {
		var buttons = tbody.querySelectorAll( '.iue-bulk-job-run' );

		for ( var i = 0; i < buttons.length; i++ ) {
			buttons[ i ].disabled    = true;
			buttons[ i ].textContent = cfg.i18n && cfg.i18n.processing ? cfg.i18n.processing : '…';
		}
	}

	// Explicit opt-in from the admin, only offered on a stalled job.
	tbody.addEventListener( 'click', function ( e ) {
		var btn = e.target && e.target.closest ? e.target.closest( '.iue-bulk-job-run' ) : null;

		if ( ! btn ) {
			return;
		}

		e.preventDefault();
		manual = true;
		markManualButtons();
		schedule( 0 );
	} );

	if ( '1' === table.getAttribute( 'data-active' ) ) {
		schedule( interval );
	}
}() );
