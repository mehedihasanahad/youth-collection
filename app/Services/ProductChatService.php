<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Setting;
use App\Support\PhoneNumber;

/**
 * Builds the "chat with us about this product" deep links shown on the product
 * detail page. Everything is driven by Store Settings → Product Chat Buttons.
 */
final class ProductChatService
{
    public const DEFAULT_TEMPLATE = "Hi! I'm interested in this product:\n\n{product_name}\nPrice: {price}\n{product_url}";

    /**
     * @return array{whatsapp: ?string, messenger: ?string, message: string, enabled: bool}
     */
    public function linksFor(Product $product, string $locale = 'en'): array
    {
        $message = $this->message($product, $locale);

        $whatsapp = $this->whatsappUrl($message);
        $messenger = $this->messengerUrl();

        return [
            'whatsapp' => $whatsapp,
            'messenger' => $messenger,
            // Messenger cannot be pre-filled, so the page copies this to the
            // clipboard for the customer to paste into the chat.
            'message' => $message,
            'enabled' => $whatsapp !== null || $messenger !== null,
        ];
    }

    private function whatsappUrl(string $message): ?string
    {
        if (Setting::get('chat_whatsapp_enabled', '0') !== '1') {
            return null;
        }

        $number = PhoneNumber::tryFrom(Setting::get('chat_whatsapp_number', ''));

        if (! $number) {
            // Allow non-Bangladesh numbers entered in full international form.
            $digits = preg_replace('/\D/', '', (string) Setting::get('chat_whatsapp_number', '')) ?? '';

            return strlen($digits) >= 10
                ? 'https://wa.me/'.$digits.'?text='.rawurlencode($message)
                : null;
        }

        return 'https://wa.me/'.$number->international().'?text='.rawurlencode($message);
    }

    /**
     * Deliberately no ?text= parameter. Messenger routes a pre-filled message into
     * the legacy unencrypted thread, which the app then shows as "sent before this
     * chat was secured with end-to-end encryption. You can't reply, react or
     * forward." A bare m.me link opens a normal, replyable conversation.
     */
    private function messengerUrl(): ?string
    {
        if (Setting::get('chat_messenger_enabled', '0') !== '1') {
            return null;
        }

        $page = $this->messengerPageHandle((string) Setting::get('chat_messenger_id', ''));

        return $page === null ? null : 'https://m.me/'.rawurlencode($page);
    }

    /**
     * Reduce whatever the admin pasted to the handle m.me expects. Accepts a bare
     * username, a numeric page id, or a full m.me / messenger.com / facebook.com
     * URL, with or without a scheme.
     */
    private function messengerPageHandle(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // facebook.com/profile.php?id=... keeps the page id in the query string.
        if (str_contains($value, '?')) {
            parse_str((string) parse_url($value, PHP_URL_QUERY), $query);

            if (! empty($query['id'])) {
                return trim((string) $query['id']) ?: null;
            }
        }

        $value = (string) preg_replace('#^https?://#i', '', $value);
        // messenger.com addresses the thread as /t/<page>.
        $value = (string) preg_replace('#^(?:www\.|web\.|m\.)?messenger\.com/(?:t/)?#i', '', $value);
        $value = (string) preg_replace('#^(?:www\.|web\.|m\.)?facebook\.com/(?:messages/t/)?#i', '', $value);
        $value = (string) preg_replace('#^(?:m\.me|fb\.me|fb\.com)/#i', '', $value);
        $value = explode('?', $value)[0];

        // Keep only the first path segment.
        $segment = explode('/', trim($value, '/'))[0];

        return $segment === '' ? null : $segment;
    }

    private function message(Product $product, string $locale): string
    {
        $translation = $product->getTranslation($locale) ?? $product->getTranslation('en');
        $slug = $translation?->slug;

        $replacements = [
            '{product_name}' => $translation?->name ?? $product->sku ?? '',
            '{price}' => '৳'.number_format((float) $product->current_price, 0),
            '{product_url}' => $slug ? route('product.show', $slug) : url('/'),
            '{sku}' => $product->sku ?? '',
        ];

        $template = (string) Setting::get('chat_message_template', self::DEFAULT_TEMPLATE);

        if (trim($template) === '') {
            $template = self::DEFAULT_TEMPLATE;
        }

        return strtr($template, $replacements);
    }
}
