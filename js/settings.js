/**
 * Custom Guest Authors — Settings Page
 *
 * Bug 1 fix: Radio cards and checkbox cards update their visual state
 *            (.selected / .wpcp-checkbox-card--checked) immediately on
 *            interaction, instead of only reflecting the saved value at
 *            page load.
 *
 * Bug 2 fix: The join style preview on the Display tab updates live as
 *            the user switches between Natural, Comma, and Ampersand
 *            options, instead of showing a static snapshot of the saved
 *            setting.
 *
 * Data passed from PHP via wp_localize_script( 'cga-settings', 'cgaSettings', ... ):
 *   cgaSettings.previewNames  — array of three author name strings
 *   cgaSettings.i18nAnd       — translated "and" conjunction
 *
 * Depends on: nothing (vanilla JS, no jQuery required)
 */
( function () {
    'use strict';

    document.addEventListener( 'DOMContentLoaded', function () {
        var form = document.querySelector( '.cga-wrap form' );
        if ( ! form ) {
            return;
        }

        // ── Bug 2: live join-style preview ──────────────────────────────────

        var preview3 = document.getElementById( 'cga-preview-3' );
        var preview2 = document.getElementById( 'cga-preview-2' );
        var names    = ( window.cgaSettings && cgaSettings.previewNames ) ? cgaSettings.previewNames : [ 'Author A', 'Author B', 'Author C' ];
        var i18nAnd  = ( window.cgaSettings && cgaSettings.i18nAnd )      ? cgaSettings.i18nAnd      : 'and';

        /**
         * Compute the two preview strings for a given join style.
         *
         * @param {string} style  'natural' | 'comma' | 'ampersand'
         * @returns {{ two: string, three: string }}
         */
        function buildPreview( style ) {
            var a = names[ 0 ], b = names[ 1 ], c = names[ 2 ];
            var two, three;

            if ( 'comma' === style ) {
                two   = a + ', ' + b;
                three = a + ', ' + b + ', ' + c;
            } else if ( 'ampersand' === style ) {
                two   = a + ' & ' + b;
                three = a + ' & ' + b + ' & ' + c;
            } else {
                // natural (default)
                two   = a + ' ' + i18nAnd + ' ' + b;
                three = a + ', ' + b + ' ' + i18nAnd + ' ' + c;
            }

            return { two: two, three: three };
        }

        /**
         * Update the preview DOM elements for the given join style.
         *
         * @param {string} style
         */
        function updatePreview( style ) {
            if ( ! preview3 || ! preview2 ) {
                return;
            }
            var result   = buildPreview( style );
            preview3.textContent = result.three;
            preview2.textContent = result.two;
        }

        // ── Bug 1 + Bug 2: single delegated change listener ─────────────────

        form.addEventListener( 'change', function ( e ) {
            var input = e.target;

            // ── Radio cards (bug 1) ──────────────────────────────────────────
            if ( input.type === 'radio' && input.closest( '.wpcp-radio-card' ) ) {
                var name     = input.name;
                var allCards = form.querySelectorAll(
                    '.wpcp-radio-card input[name="' + name + '"]'
                );

                allCards.forEach( function ( sibling ) {
                    var card = sibling.closest( '.wpcp-radio-card' );
                    if ( card ) {
                        card.classList.remove( 'selected' );
                    }
                } );

                var activeCard = input.closest( '.wpcp-radio-card' );
                if ( activeCard ) {
                    activeCard.classList.add( 'selected' );
                }

                // ── Live preview (bug 2) — only for cga_join_style ───────────
                if ( 'cga_join_style' === name ) {
                    updatePreview( input.value );
                }
            }

            // ── Checkbox cards (bug 1) ───────────────────────────────────────
            if ( input.type === 'checkbox' && input.closest( '.wpcp-checkbox-card' ) ) {
                var card = input.closest( '.wpcp-checkbox-card' );
                if ( card ) {
                    card.classList.toggle( 'wpcp-checkbox-card--checked', input.checked );
                }
            }
        } );
    } );
} )();
