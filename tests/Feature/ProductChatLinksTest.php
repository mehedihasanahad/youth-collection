<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\Setting;
use App\Services\ProductChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProductChatLinksTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $product = new Product(['sku' => 'YC-001', 'price' => 1500]);

        $product->setRelation('translations', new Collection([
            new ProductTranslation(['locale' => 'en', 'name' => 'Denim Jacket', 'slug' => 'denim-jacket']),
        ]));

        return $product;
    }

    public function test_no_links_are_produced_while_both_buttons_are_disabled(): void
    {
        $links = app(ProductChatService::class)->linksFor($this->product());

        $this->assertFalse($links['enabled']);
        $this->assertNull($links['whatsapp']);
        $this->assertNull($links['messenger']);
    }

    public function test_whatsapp_link_uses_the_international_number_and_pre_filled_message(): void
    {
        Setting::set('chat_whatsapp_enabled', '1', 'chat');
        Setting::set('chat_whatsapp_number', '01712345678', 'chat');
        Setting::set('chat_message_template', 'Interested in {product_name} at {price} — {product_url} ({sku})', 'chat');

        $links = app(ProductChatService::class)->linksFor($this->product());

        $this->assertTrue($links['enabled']);
        $this->assertStringStartsWith('https://wa.me/8801712345678?text=', $links['whatsapp']);

        $message = urldecode(explode('?text=', $links['whatsapp'], 2)[1]);

        $this->assertStringContainsString('Denim Jacket', $message);
        $this->assertStringContainsString('1,500', $message);
        $this->assertStringContainsString(route('product.show', 'denim-jacket'), $message);
        $this->assertStringContainsString('YC-001', $message);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function pageIdentifiers(): array
    {
        return [
            'bare username' => ['youthcollections18'],
            'm.me url' => ['https://m.me/youthcollections18'],
            'm.me url without scheme' => ['m.me/youthcollections18'],
            'facebook url' => ['https://www.facebook.com/youthcollections18'],
            'facebook url with trailing slash' => ['https://www.facebook.com/youthcollections18/'],
            'facebook url without scheme' => ['facebook.com/youthcollections18'],
            'messenger url' => ['https://messenger.com/t/youthcollections18'],
            'padded whitespace' => ['  https://m.me/youthcollections18  '],
        ];
    }

    #[DataProvider('pageIdentifiers')]
    public function test_messenger_link_resolves_every_accepted_page_format(string $configured): void
    {
        Setting::set('chat_messenger_enabled', '1', 'chat');
        Setting::set('chat_messenger_id', $configured, 'chat');

        $links = app(ProductChatService::class)->linksFor($this->product());

        $this->assertSame('https://m.me/youthcollections18', $links['messenger']);
    }

    public function test_messenger_link_reads_the_page_id_out_of_a_profile_url(): void
    {
        Setting::set('chat_messenger_enabled', '1', 'chat');
        Setting::set('chat_messenger_id', 'https://www.facebook.com/profile.php?id=61550000000000', 'chat');

        $links = app(ProductChatService::class)->linksFor($this->product());

        $this->assertSame('https://m.me/61550000000000', $links['messenger']);
    }

    public function test_messenger_link_carries_no_prefill_parameter(): void
    {
        Setting::set('chat_messenger_enabled', '1', 'chat');
        Setting::set('chat_messenger_id', 'youthcollection', 'chat');

        $links = app(ProductChatService::class)->linksFor($this->product());

        // A ?text= prefill lands in Messenger's legacy unencrypted thread and
        // becomes unreplyable, so the link must stay bare.
        $this->assertStringNotContainsString('?', $links['messenger']);
        $this->assertStringContainsString('Denim Jacket', $links['message']);
    }

    public function test_a_button_stays_hidden_when_its_destination_is_missing(): void
    {
        Setting::set('chat_whatsapp_enabled', '1', 'chat');
        Setting::set('chat_whatsapp_number', '', 'chat');
        Setting::set('chat_messenger_enabled', '1', 'chat');
        Setting::set('chat_messenger_id', '', 'chat');

        $links = app(ProductChatService::class)->linksFor($this->product());

        $this->assertFalse($links['enabled']);
    }
}
