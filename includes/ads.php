<?php
require_once __DIR__ . '/config.php';

/**
 * Ad rendering driven by config/ads.json.
 *
 * All GPT slots on a page are collected while the page renders and defined in
 * ONE googletag.cmd.push from the footer, with services enabled exactly once.
 * Defining slots in separate pushes with repeated enableServices() calls (as the
 * reference site does) leaves the later slots unfilled.
 */

$GPT_QUEUE      = [];   // in-page slots: div id => slot config
$GPT_OOP_QUEUE  = [];   // out-of-page slots (interstitial / anchor)
$ADSENSE_LOADED = false;

function ads_network($key, $fallback = '') {
    global $ADS;
    return $ADS['network'][$key] ?? $fallback;
}

/** Turn [[300,250],"fluid"] into the JS size literal GPT wants. */
function ads_sizes_js($sizes) {
    $parts = [];
    foreach ((array)$sizes as $s) {
        if (is_array($s) && count($s) === 2) {
            $parts[] = '[' . (int)$s[0] . ', ' . (int)$s[1] . ']';
        } elseif (is_string($s)) {
            $parts[] = json_encode($s);
        }
    }
    return '[' . implode(', ', $parts) . ']';
}

/** Full GAM slot path: /<network code>/<slot name>. */
function ads_slot_path($slot) {
    return '/' . ads_network('gam_network_code', '00000000000') . '/' . $slot;
}

/**
 * Render every enabled unit configured for $position.
 * Unknown positions render nothing, so templates may reference slots a given
 * site has not configured yet.
 */
function ad_slot($position, $wrapperClass = 'my-4') {
    global $ADS, $GPT_QUEUE, $GPT_OOP_QUEUE, $ADSENSE_LOADED;

    $units = $ADS['positions'][$position] ?? [];
    $units = array_values(array_filter($units, function ($u) { return !empty($u['enabled']); }));
    if (!$units) return;

    echo '<div class="ad-zone text-center ' . e($wrapperClass) . '" data-ad-position="' . e($position) . '">' . "\n";

    foreach ($units as $i => $unit) {
        switch ($unit['type'] ?? '') {

            case 'label':
                echo '<div class="text-[11px] uppercase tracking-widest text-gray-400 mb-1">'
                   . e($unit['text'] ?? 'Advertisements') . "</div>\n";
                break;

            case 'adsense_loader':
                if (!$ADSENSE_LOADED) {
                    $ADSENSE_LOADED = true;
                    echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client='
                       . e(ads_network('adsense_client')) . '" crossorigin="anonymous"></script>' . "\n";
                }
                break;

            case 'adsense':
                echo '<ins class="adsbygoogle" style="display:block"'
                   . ' data-ad-client="' . e(ads_network('adsense_client')) . '"'
                   . ' data-ad-slot="' . e($unit['slot'] ?? '') . '"'
                   . ' data-ad-format="' . e($unit['format'] ?? 'auto') . '"'
                   . ' data-full-width-responsive="true"></ins>' . "\n"
                   . '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>' . "\n";
                break;

            case 'gpt':
                $div = $unit['div'] ?? ('gpt-' . $position . '-' . $i);
                $GPT_QUEUE[$div] = [
                    'path'  => ads_slot_path($unit['slot'] ?? ''),
                    'sizes' => ads_sizes_js($unit['sizes'] ?? [[300, 250]]),
                ];
                printf(
                    '<div id="%s" style="min-width:%dpx;min-height:%dpx;margin:0 auto;"></div>' . "\n",
                    e($div),
                    (int)($unit['min_width'] ?? 300),
                    (int)($unit['min_height'] ?? 250)
                );
                break;

            case 'gpt_interstitial':
                $GPT_OOP_QUEUE[] = ['format' => 'INTERSTITIAL', 'path' => ads_slot_path($unit['slot'] ?? '')];
                break;

            case 'gpt_anchor':
                $GPT_OOP_QUEUE[] = [
                    'format' => strtoupper($unit['anchor'] ?? 'BOTTOM') . '_ANCHOR',
                    'path'   => ads_slot_path($unit['slot'] ?? ''),
                ];
                break;

            case 'html':
                echo $unit['html'] ?? '';
                break;
        }
    }

    echo "</div>\n";
}

/**
 * Emit the single GPT definition block for everything queued so far.
 * Called from the footer, after all ad_slot() calls have run.
 */
function ads_flush_gpt() {
    global $GPT_QUEUE, $GPT_OOP_QUEUE;
    if (!$GPT_QUEUE && !$GPT_OOP_QUEUE) return;

    echo '<script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js" crossorigin="anonymous"></script>' . "\n";
    echo "<script>\nwindow.googletag = window.googletag || {cmd: []};\ngoogletag.cmd.push(function () {\n";

    foreach ($GPT_QUEUE as $div => $cfg) {
        printf("  googletag.defineSlot(%s, %s, %s).addService(googletag.pubads());\n",
            json_encode($cfg['path']), $cfg['sizes'], json_encode($div));
    }

    foreach ($GPT_OOP_QUEUE as $i => $oop) {
        printf("  var oop%d = googletag.defineOutOfPageSlot(%s, googletag.enums.OutOfPageFormat.%s);\n",
            $i, json_encode($oop['path']), $oop['format']);
        printf("  if (oop%d) { oop%d.addService(googletag.pubads()); }\n", $i, $i);
    }

    echo "  googletag.pubads().enableSingleRequest();\n";
    echo "  googletag.pubads().collapseEmptyDivs();\n";
    echo "  googletag.enableServices();\n";

    foreach (array_keys($GPT_QUEUE) as $div) {
        printf("  googletag.display(%s);\n", json_encode($div));
    }
    foreach (array_keys($GPT_OOP_QUEUE) as $i) {
        printf("  if (oop%d) { googletag.display(oop%d); }\n", $i, $i);
    }

    echo "});\n</script>\n";
}

/** Analytics / conversion pixels, from the global_pixels position. */
function render_pixels() {
    global $ADS;
    foreach ($ADS['positions']['global_pixels'] ?? [] as $px) {
        if (empty($px['enabled']) || empty($px['id'])) continue;
        $id   = $px['id'];
        $jsId = json_encode($id);

        if ($px['type'] === 'meta_pixel') {
            echo <<<HTML
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', {$jsId});
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" alt=""
src="https://www.facebook.com/tr?id={$id}&ev=PageView&noscript=1"></noscript>

HTML;
        } elseif ($px['type'] === 'snap_pixel') {
            echo <<<HTML
<script>
(function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){
a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
a.queue=[];var s='script',r=t.createElement(s);r.async=!0;r.src=n;
var u=t.getElementsByTagName(s)[0];u.parentNode.insertBefore(r,u);})(window,
document,'https://sc-static.net/scevent.min.js');
snaptr('init', {$jsId}, {});
snaptr('track', 'PAGE_VIEW');
</script>

HTML;
        } elseif ($px['type'] === 'ga4') {
            echo <<<HTML
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', {$jsId});
</script>

HTML;
        }
    }
}

/** Fire a conversion event on whichever pixels are enabled. */
function pixel_event($event, $params = []) {
    global $ADS;
    $map = [
        'meta_pixel' => ['ViewContent' => 'ViewContent', 'AddToCart' => 'AddToCart',
                         'InitiateCheckout' => 'InitiateCheckout', 'Purchase' => 'Purchase'],
        'snap_pixel' => ['ViewContent' => 'VIEW_CONTENT', 'AddToCart' => 'ADD_CART',
                         'InitiateCheckout' => 'START_CHECKOUT', 'Purchase' => 'PURCHASE'],
    ];
    $json  = json_encode($params, JSON_UNESCAPED_SLASHES);
    $lines = [];
    foreach ($ADS['positions']['global_pixels'] ?? [] as $px) {
        if (empty($px['enabled']) || empty($px['id'])) continue;
        $name = $map[$px['type']][$event] ?? null;
        if (!$name) continue;
        $fn = $px['type'] === 'meta_pixel' ? 'fbq' : 'snaptr';
        $lines[] = "if (window.$fn) { $fn('track', " . json_encode($name) . ", $json); }";
    }
    if ($lines) {
        echo "<script>\n" . implode("\n", $lines) . "\n</script>\n";
    }
}
