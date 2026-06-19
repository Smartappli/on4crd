<?php
declare(strict_types=1);

$locale = current_locale();
$t = i18n_domain_locale('donation', $locale);
$tr = static fn(string $key): string => (string) ($t[$key] ?? $key);
$donationUrl = route_url_with_locale('donation', $locale);

set_page_meta([
    'title' => $tr('title'),
    'description' => $tr('intro'),
    'canonical' => $donationUrl,
    'schema_type' => 'DonateAction',
    'json_ld' => [
        '@context' => 'https://schema.org',
        '@type' => 'DonateAction',
        'name' => $tr('title'),
        'description' => $tr('intro'),
        'url' => $donationUrl,
        'recipient' => [
            '@type' => 'Organization',
            'name' => 'Radio Club Durnal ON4CRD',
            'url' => route_url_with_locale('home', $locale),
        ],
    ],
]);

$supportItems = [$tr('support_item_1'), $tr('support_item_2'), $tr('support_item_3')];
$processItems = [$tr('process_item_1'), $tr('process_item_2'), $tr('process_item_3')];
$contactCaptcha = public_form_captcha_challenge('footer_contact');
$contactCaptchaLabel = public_form_captcha_label($contactCaptcha, $locale);

$renderList = static function (array $items): string {
    $html = '';
    foreach ($items as $item) {
        $html .= '<li class="rounded-xl border border-slate-200 bg-white p-4 text-sm leading-relaxed text-slate-700">' . e((string) $item) . '</li>';
    }

    return $html;
};

$content = '<section class="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-8 shadow-sm">'
    . '<span class="inline-flex rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">' . e($tr('badge')) . '</span>'
    . '<h1 class="mt-4 text-3xl font-extrabold text-slate-900 lg:text-4xl">' . e($tr('title')) . '</h1>'
    . '<p class="mt-3 max-w-3xl text-slate-700">' . e($tr('intro')) . '</p>'
    . '<div class="mt-5 flex flex-wrap gap-3">'
    . '<a class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700" href="#donation-contact">' . e($tr('primary_cta')) . '</a>'
    . '<a class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('sponsoring')) . '">' . e($tr('sponsoring_cta')) . '</a>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('support_title')) . '</h2>'
    . '<ul class="mt-4 grid gap-3">' . $renderList($supportItems) . '</ul>'
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-sm">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e($tr('process_title')) . '</h2>'
    . '<p class="mt-2 text-sm text-slate-700">' . e($tr('process_intro')) . '</p>'
    . '<ul class="mt-4 grid gap-3">' . $renderList($processItems) . '</ul>'
    . '</article>'
    . '</section>'
    . '<section id="donation-contact" class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-6 lg:grid-cols-[.9fr_1.1fr] lg:items-start">'
    . '<div><h2 class="text-xl font-bold text-slate-900">' . e($tr('contact_title')) . '</h2>'
    . '<p class="mt-2 text-sm leading-6 text-slate-700">' . e($tr('contact_intro')) . '</p>'
    . '<p class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">' . e($tr('security_note')) . '</p></div>'
    . '<form class="grid gap-3" method="post" action="' . e(route_url('footer_contact')) . '">'
    . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
    . '<input type="hidden" name="return_route" value="donation">'
    . '<input type="text" name="contact_website" value="" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden">'
    . '<label for="donation-contact-name" class="sr-only">' . e($tr('contact_name')) . '</label>'
    . '<input id="donation-contact-name" type="text" name="name" placeholder="' . e($tr('contact_name')) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">'
    . '<label for="donation-contact-email" class="sr-only">' . e($tr('contact_email')) . '</label>'
    . '<input id="donation-contact-email" type="email" name="email" placeholder="' . e($tr('contact_email')) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">'
    . '<label for="donation-contact-message" class="sr-only">' . e($tr('contact_message')) . '</label>'
    . '<textarea id="donation-contact-message" name="message" placeholder="' . e($tr('contact_message')) . '" rows="5" maxlength="2000" data-wysiwyg="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>'
    . '<label for="donation-contact-captcha" class="text-xs font-semibold text-slate-600">' . e($contactCaptchaLabel) . '</label>'
    . '<input id="donation-contact-captcha" type="text" inputmode="numeric" pattern="[0-9]*" name="contact_captcha" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">'
    . '<button type="submit" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">' . e($tr('contact_send')) . '</button>'
    . '</form>'
    . '</div>'
    . '</section>';

echo render_layout($content, $tr('title'));
