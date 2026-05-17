/**
 * Copyright © Develo Design. All rights reserved.
 * See COPYING.txt for license details.
 *
 * Admin RequireJS mixin for Magento_ProductVideo/js/get-video-information.
 *
 * Extends the native videoData and productVideoLoader widgets so that
 * Cloudflare Stream URLs are recognised as valid video URLs, the YouTube/Vimeo
 * metadata AJAX call is short-circuited for Cloudflare entries, and the
 * in-modal preview player does not throw "Unknown video type".
 *
 * MAINTENANCE NOTE — duplicate regex:
 * The JS regex below (CLOUDFLARE_URL_RE) is the browser-side equivalent of the
 * PHP regex in Develo\CloudflareVideo\Model\UidExtractor. Both must be kept in
 * sync whenever Cloudflare changes its URL scheme. See task-008 README for
 * details.
 */
define(['jquery'], function ($) {
    'use strict';

    /**
     * Recognises Cloudflare Stream URLs and bare 32-char hex UIDs.
     * Capturing group 1: UID from a full URL.
     * Capturing group 2: UID when input is a bare UID.
     *
     * NOTE: This regex is intentionally duplicated from the PHP UidExtractor.
     * Update both locations together when Cloudflare changes its URL structure.
     */
    var CLOUDFLARE_URL_RE =
        /^(?:https?:\/\/(?:customer-[a-z0-9]+\.cloudflarestream\.com|watch\.cloudflarestream\.com|iframe\.videodelivery\.net|videodelivery\.net)\/([a-f0-9]{32})(?:\/|\?|$)|^([a-f0-9]{32})$)/;

    return function (OriginalWidget) {

        // ------------------------------------------------------------------
        // 1. Extend mage.videoData — wrap _validateURL and _onRequestHandler
        // ------------------------------------------------------------------
        $.widget('mage.videoData', $.mage.videoData, {

            /**
             * Wrap _validateURL so Cloudflare URLs are accepted.
             * For non-Cloudflare URLs the original method is invoked via _super().
             *
             * @param {String} href
             * @param {Boolean} [forceVideo]
             * @returns {Object|Boolean}
             */
            _validateURL: function (href, forceVideo) {
                var m = CLOUDFLARE_URL_RE.exec(href);

                if (m) {
                    return {
                        id: m[1] || m[2],
                        type: 'cloudflare',
                        s: '',
                        useYoutubeNocookie: false
                    };
                }

                return this._super(href, forceVideo);
            },

            /**
             * Wrap _onRequestHandler to short-circuit the AJAX call for
             * Cloudflare URLs and synchronously fire the information events.
             * For non-Cloudflare URLs the original flow continues via _super().
             */
            _onRequestHandler: function () {
                var url = this.element.val(),
                    videoInfo;

                if (!url) {
                    return this._super();
                }

                videoInfo = this._validateURL(url);

                if (videoInfo && videoInfo.type === 'cloudflare') {
                    // Prevent duplicate processing (mirrors the upstream guard)
                    if (this._currentVideoUrl === url) {
                        return;
                    }
                    this._currentVideoUrl = url;

                    this.element.trigger(this._REQUEST_VIDEO_INFORMATION_TRIGGER, {url: url});

                    this._videoInformation = {
                        duration: '',
                        channel: '',
                        channelId: '',
                        uploaded: '',
                        title: '',
                        description: '',
                        thumbnail: '',
                        videoId: videoInfo.id,
                        videoProvider: 'cloudflare',
                        useYoutubeNocookie: false
                    };
                    this.element.trigger(
                        this._UPDATE_VIDEO_INFORMATION_TRIGGER,
                        this._videoInformation
                    );
                    this.element.trigger(this._FINISH_UPDATE_INFORMATION_TRIGGER, true);

                    return;
                }

                return this._super();
            }
        });

        // ------------------------------------------------------------------
        // 2. Extend mage.productVideoLoader — prevent "Unknown video type"
        //    error when the element carries data-type="cloudflare".
        // ------------------------------------------------------------------
        $.widget('mage.productVideoLoader', $.mage.productVideoLoader, {

            /**
             * Override _create so the cloudflare case is a graceful no-op
             * instead of throwing "Unknown video type".
             * YouTube and Vimeo elements fall through to the original _super().
             */
            _create: function () {
                if (this.element.data('type') === 'cloudflare') {
                    // No preview player is rendered; the poster image is shown
                    // by the existing gallery thumbnail — nothing to do here.
                    return;
                }

                return this._super();
            }
        });

        return OriginalWidget;
    };
});
