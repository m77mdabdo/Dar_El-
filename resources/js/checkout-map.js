/**
 * Checkout-only map picker (Leaflet + OpenStreetMap tiles + Nominatim
 * reverse geocoding — all free, no API key/billing account). A separate
 * Vite entry (see vite.config.js), loaded only from checkout/show.blade.php,
 * so the ~40KB Leaflet bundle never ships to every other storefront page —
 * same reasoning as admin-products.js being its own entry rather than
 * folding into the universal app.js.
 */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Leaflet's default marker icon URLs are hard-coded relative paths that
// break once bundled — this is the standard fix, pointing them at the
// actual hashed asset URLs Vite produced for these images.
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// Cairo, not Egypt's geometric center — this is a COD/Egypt-only store, and
// most customers are somewhere in the Cairo/Delta area, so the map opens
// already close to where most people need it rather than centered on the
// mostly-empty desert that Egypt's true geographic middle falls in.
const EGYPT_CENTER = [30.0444, 31.2357];
const DEFAULT_ZOOM = 6;
const PIN_ZOOM = 15;

let lastGeocodeAt = 0;
let geocodeAbortController = null;

/**
 * Nominatim's usage policy caps this at 1 request/second and asks that
 * callers identify themselves via a valid HTTP Referer or User-Agent.
 * Browser JS can't set a custom User-Agent (browsers block script-set
 * values), so the Referer — which the browser sends automatically for this
 * same-page fetch, since we don't override referrerPolicy — is the
 * identification mechanism their policy expects from a client-side app
 * like this one. The timestamp guard below is belt-and-suspenders: map
 * clicks/drags are already discrete, human-paced events that can't
 * realistically exceed 1/sec on their own.
 */
function reverseGeocode(lat, lng, { locale, onStart, onSuccess, onError }) {
    const wait = Math.max(0, 1000 - (Date.now() - lastGeocodeAt));

    if (geocodeAbortController) geocodeAbortController.abort();
    const controller = new AbortController();
    geocodeAbortController = controller;

    if (onStart) onStart();

    setTimeout(() => {
        if (controller.signal.aborted) return;
        lastGeocodeAt = Date.now();

        const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${encodeURIComponent(locale || 'ar')}`;

        fetch(url, { signal: controller.signal })
            .then((res) => {
                if (!res.ok) throw new Error('Nominatim request failed');
                return res.json();
            })
            .then((data) => { if (onSuccess) onSuccess(data); })
            .catch((err) => {
                if (err.name !== 'AbortError' && onError) onError(err);
            });
    }, wait);
}

/**
 * options: { containerId, locale, onPositionChange(lat,lng), onGeocodeStart(),
 * onGeocodeSuccess(nominatimJson), onGeocodeError(err) }
 *
 * Exposes window.djCheckoutMapSetPosition(lat, lng) so the existing "Use My
 * Current Location" button (its own inline script in checkout/show.blade.php)
 * can drive the same map + reverse-geocode flow after getting a GPS fix,
 * rather than duplicating map logic there.
 */
window.djInitCheckoutMap = function (options) {
    const container = document.getElementById(options.containerId);
    if (!container) return;

    const map = L.map(options.containerId, { center: EGYPT_CENTER, zoom: DEFAULT_ZOOM });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
    }).addTo(map);

    let marker = null;

    function placeMarkerAndGeocode(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', () => {
                const pos = marker.getLatLng();
                placeMarkerAndGeocode(pos.lat, pos.lng);
            });
        }

        if (options.onPositionChange) options.onPositionChange(lat, lng);

        reverseGeocode(lat, lng, {
            locale: options.locale,
            onStart: options.onGeocodeStart,
            onSuccess: options.onGeocodeSuccess,
            onError: options.onGeocodeError,
        });
    }

    map.on('click', (e) => placeMarkerAndGeocode(e.latlng.lat, e.latlng.lng));

    window.djCheckoutMapSetPosition = function (lat, lng) {
        map.setView([lat, lng], PIN_ZOOM);
        placeMarkerAndGeocode(lat, lng);
    };
};
