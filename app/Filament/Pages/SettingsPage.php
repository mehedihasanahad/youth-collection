<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Services\ProductChatService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Store Settings';

    protected static ?string $title = 'Store Settings';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.settings-page';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isSuperAdmin() || $user->hasPermission('edit_settings'));
    }

    // Form state
    public array $general = [];

    public array $contact = [];

    public array $payment = [];

    public array $social = [];

    public array $oauth = [];

    public array $homepage = [];

    public array $seo = [];

    public array $pixel = [];

    public array $chat = [];

    // Branding / media — kept flat (no statePath) so FileUpload can store files properly
    public ?array $site_logo = [];

    public ?array $site_favicon = [];

    public ?array $promo_banner_image = [];

    public ?array $og_image = [];

    public function mount(): void
    {
        $this->general = [
            'site_name' => Setting::get('site_name', config('app.name')),
            'site_tagline' => Setting::get('site_tagline', ''),
            'site_description' => Setting::get('site_description', ''),
            'default_locale' => Setting::get('default_locale', 'en'),
            'announcement_bar_text' => Setting::get('announcement_bar_text', ''),
        ];

        $this->contact = [
            'contact_email' => Setting::get('contact_email', ''),
            'contact_phone' => Setting::get('contact_phone', ''),
            'whatsapp_number' => Setting::get('whatsapp_number', ''),
            'contact_address' => Setting::get('contact_address', ''),
            'contact_city' => Setting::get('contact_city', ''),
        ];

        $this->payment = [
            'bkash_merchant_number' => Setting::get('bkash_merchant_number', env('BKASH_MERCHANT_NUMBER', '')),
            'bkash_merchant_name' => Setting::get('bkash_merchant_name', env('BKASH_MERCHANT_NAME', '')),
            'nagad_merchant_number' => Setting::get('nagad_merchant_number', env('NAGAD_MERCHANT_NUMBER', '')),
            'nagad_merchant_name' => Setting::get('nagad_merchant_name', env('NAGAD_MERCHANT_NAME', '')),
            'sslcommerz_store_id' => Setting::get('sslcommerz_store_id', ''),
            'sslcommerz_store_password' => Setting::get('sslcommerz_store_password', ''),
            'sslcommerz_is_live' => (bool) Setting::get('sslcommerz_is_live', '0'),
            'cod_enabled' => (bool) Setting::get('cod_enabled', '1'),
        ];

        $this->social = [
            'facebook_url' => Setting::get('facebook_url', ''),
            'instagram_url' => Setting::get('instagram_url', ''),
            'youtube_url' => Setting::get('youtube_url', ''),
            'twitter_url' => Setting::get('twitter_url', ''),
        ];

        $this->oauth = [
            'google_login_enabled' => (bool) Setting::get('google_login_enabled', '0'),
            'google_client_id' => Setting::get('google_client_id', ''),
            'google_client_secret' => Setting::get('google_client_secret', ''),
        ];

        $this->seo = [
            'meta_title' => Setting::get('meta_title', ''),
            'meta_description' => Setting::get('meta_description', ''),
            'meta_keywords' => Setting::get('meta_keywords', ''),
            'robots_txt' => Setting::get('robots_txt', "User-agent: *\nAllow: /\nDisallow: /admin/\nSitemap: ".url('/sitemap.xml')),
        ];

        $this->pixel = [
            'facebook_pixel_enabled' => (bool) Setting::get('facebook_pixel_enabled', '0'),
            'facebook_pixel_id' => Setting::get('facebook_pixel_id', ''),
        ];

        $this->chat = [
            'chat_whatsapp_enabled' => (bool) Setting::get('chat_whatsapp_enabled', '0'),
            'chat_whatsapp_number' => Setting::get('chat_whatsapp_number', Setting::get('whatsapp_number', '')),
            'chat_messenger_enabled' => (bool) Setting::get('chat_messenger_enabled', '0'),
            'chat_messenger_id' => Setting::get('chat_messenger_id', ''),
            'chat_message_template' => Setting::get('chat_message_template', ProductChatService::DEFAULT_TEMPLATE),
        ];

        $this->homepage = [
            'promo_banner_enabled' => (bool) Setting::get('promo_banner_enabled', '1'),
            'promo_banner_label' => Setting::get('promo_banner_label', 'Up To'),
            'promo_banner_headline' => Setting::get('promo_banner_headline', '20% OFF'),
            'promo_banner_subtext' => Setting::get('promo_banner_subtext', 'On New Collection'),
            'promo_banner_button_text' => Setting::get('promo_banner_button_text', 'Shop Now'),
            'promo_banner_button_url' => Setting::get('promo_banner_button_url', ''),
        ];

        // Hydrate flat FileUpload properties from stored paths.
        // Filament v3 FileUpload expects [path => path] when loading existing files.
        $logoPath = Setting::get('site_logo', '');
        $faviconPath = Setting::get('site_favicon', '');

        $this->site_logo = $logoPath ? [$logoPath => $logoPath] : [];
        $this->site_favicon = $faviconPath ? [$faviconPath => $faviconPath] : [];

        $promoImagePath = Setting::get('promo_banner_image', '');
        $this->promo_banner_image = $promoImagePath ? [$promoImagePath => $promoImagePath] : [];

        $ogImagePath = Setting::get('og_image', '');
        $this->og_image = $ogImagePath ? [$ogImagePath => $ogImagePath] : [];
    }

    protected function getForms(): array
    {
        return ['brandingForm', 'generalForm', 'contactForm', 'paymentForm', 'socialForm', 'oauthForm', 'chatForm', 'homepageForm', 'homepageImageForm', 'seoForm', 'ogImageForm', 'pixelForm'];
    }

    public function brandingForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('site_logo')
                ->label('Store Logo')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->directory('settings')
                ->imagePreviewHeight('100')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
                ->maxSize(1024)
                ->nullable()
                ->helperText('Displayed in the storefront header. Recommended: 200×60px, transparent PNG or SVG.'),

            Forms\Components\FileUpload::make('site_favicon')
                ->label('Favicon')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->directory('settings')
                ->imagePreviewHeight('100')
                ->acceptedFileTypes(['image/x-icon', 'image/png', 'image/webp'])
                ->maxSize(256)
                ->nullable()
                ->helperText('Browser tab icon. 32×32px PNG or ICO recommended.'),
        ])->columns(2);
        // No ->statePath() — binds directly to $this->site_logo and $this->site_favicon
    }

    public function generalForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('site_name')->required()->maxLength(100),
            Forms\Components\TextInput::make('site_tagline')->maxLength(191)->nullable(),
            Forms\Components\Textarea::make('site_description')->rows(2)->nullable()->columnSpanFull(),
            Forms\Components\Section::make('Announcement Bar')->schema([
                Forms\Components\TextInput::make('announcement_bar_text')
                    ->label('Announcement Text')
                    ->nullable()
                    ->maxLength(500)
                    ->columnSpanFull()
                    ->helperText('Shown in the top bar. Leave blank to hide. Supports basic HTML like <strong>. E.g.: Free shipping over ৳999 &amp;nbsp;|&amp;nbsp; Use code &lt;strong&gt;WELCOME10&lt;/strong&gt; for 10% off'),
            ])->columnSpanFull(),
        ])->statePath('general')->columns(2);
    }

    public function contactForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('contact_email')
                ->label('Contact Email')
                ->email()
                ->nullable(),
            Forms\Components\TextInput::make('contact_phone')
                ->label('Phone Number')
                ->nullable()
                ->placeholder('+880 1XXX-XXXXXX'),
            Forms\Components\TextInput::make('whatsapp_number')
                ->label('WhatsApp Number')
                ->nullable()
                ->placeholder('+880 1XXX-XXXXXX')
                ->helperText('Used for the WhatsApp chat button on the storefront.'),
            Forms\Components\TextInput::make('contact_city')
                ->label('City')
                ->nullable(),
            Forms\Components\Textarea::make('contact_address')
                ->label('Full Address')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ])->statePath('contact')->columns(2);
    }

    public function paymentForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('bKash')->schema([
                Forms\Components\TextInput::make('bkash_merchant_number')->label('Merchant Number')->nullable(),
                Forms\Components\TextInput::make('bkash_merchant_name')->label('Merchant Name')->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Nagad')->schema([
                Forms\Components\TextInput::make('nagad_merchant_number')->label('Merchant Number')->nullable(),
                Forms\Components\TextInput::make('nagad_merchant_name')->label('Merchant Name')->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('SSLCommerz')->schema([
                Forms\Components\TextInput::make('sslcommerz_store_id')
                    ->label('Store ID')->nullable(),
                Forms\Components\TextInput::make('sslcommerz_store_password')
                    ->label('Store Password')->password()->revealable()->nullable(),
                Forms\Components\Toggle::make('sslcommerz_is_live')
                    ->label('Live Mode (off = sandbox)')
                    ->helperText('Enable only after testing on sandbox.')
                    ->inline(false),
            ])->columns(2),

            Forms\Components\Section::make('Other')->schema([
                Forms\Components\Toggle::make('cod_enabled')->label('Cash on Delivery Enabled')->default(true)->inline(false),
            ])->columns(2),
        ])->statePath('payment');
    }

    public function socialForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('facebook_url')->url()->nullable()->label('Facebook'),
            Forms\Components\TextInput::make('instagram_url')->url()->nullable()->label('Instagram'),
            Forms\Components\TextInput::make('youtube_url')->url()->nullable()->label('YouTube'),
            Forms\Components\TextInput::make('twitter_url')->url()->nullable()->label('Twitter / X'),
        ])->statePath('social')->columns(2);
    }

    public function homepageImageForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('promo_banner_image')
                ->label('Promo Banner Image')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->directory('banners')
                ->imagePreviewHeight('160')
                ->maxSize(3072)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->nullable()
                ->helperText('📐 Must be exactly 1200 × 450 px | JPEG / PNG / WebP | Max 3 MB.')
                ->rules([
                    'nullable',
                    'image',
                    'mimes:jpeg,jpg,png,webp',
                    'max:3072',
                    'dimensions:width=1200,height=450',
                ])
                ->validationMessages([
                    'image' => 'The file must be a valid image.',
                    'mimes' => 'Only JPEG, PNG, and WebP images are accepted.',
                    'max' => 'The image must not exceed 3 MB.',
                    'dimensions' => 'Image must be exactly 1200 × 450 px.',
                ]),
        ]);
        // No statePath — binds directly to $this->promo_banner_image
    }

    public function ogImageForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('og_image')
                ->label('Default OG Image')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->directory('settings')
                ->imagePreviewHeight('120')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->nullable()
                ->helperText('Recommended: 1200×630px, max 2 MB. Shown when pages are shared on Facebook, WhatsApp, Twitter, etc.'),
        ]);
        // No statePath — binds directly to $this->og_image
    }

    public function homepageForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Promo Banner')->schema([
                Forms\Components\Toggle::make('promo_banner_enabled')
                    ->label('Show Promo Banner')
                    ->helperText('Display the promotional banner on the home page.')
                    ->inline(false)
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('promo_banner_label')
                    ->label('Label (small text above headline)')
                    ->maxLength(50)
                    ->nullable()
                    ->placeholder('Up To')
                    ->visible(fn ($get) => $get('promo_banner_enabled')),
                Forms\Components\TextInput::make('promo_banner_headline')
                    ->label('Headline')
                    ->maxLength(50)
                    ->nullable()
                    ->placeholder('20% OFF')
                    ->visible(fn ($get) => $get('promo_banner_enabled')),
                Forms\Components\TextInput::make('promo_banner_subtext')
                    ->label('Sub-text (below headline)')
                    ->maxLength(100)
                    ->nullable()
                    ->placeholder('On New Collection')
                    ->visible(fn ($get) => $get('promo_banner_enabled')),
                Forms\Components\TextInput::make('promo_banner_button_text')
                    ->label('Button Label')
                    ->maxLength(50)
                    ->nullable()
                    ->placeholder('Shop Now')
                    ->visible(fn ($get) => $get('promo_banner_enabled')),
                Forms\Components\TextInput::make('promo_banner_button_url')
                    ->label('Button URL')
                    ->url()
                    ->nullable()
                    ->placeholder('Leave blank to link to discounted products')
                    ->visible(fn ($get) => $get('promo_banner_enabled')),
            ])->columns(2),
        ])->statePath('homepage');
    }

    public function seoForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Search Engine Defaults')
                ->description('Fallback meta tags used on pages that do not set their own SEO fields.')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Default Meta Title')
                        ->maxLength(70)
                        ->helperText('Recommended: 50–70 characters.')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('meta_description')
                        ->label('Default Meta Description')
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Recommended: 120–160 characters.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('meta_keywords')
                        ->label('Default Keywords')
                        ->maxLength(500)
                        ->helperText('Comma-separated, e.g. hijab, modest fashion, abaya')
                        ->columnSpanFull(),
                ])->columns(1),

            Forms\Components\Section::make('Robots.txt')
                ->schema([
                    Forms\Components\Textarea::make('robots_txt')
                        ->label('robots.txt Content')
                        ->rows(8)
                        ->columnSpanFull()
                        ->helperText('Served at /robots.txt. Changes take effect immediately.'),
                ])->columns(1),
        ])->statePath('seo');
    }

    public function oauthForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Google OAuth')->schema([
                Forms\Components\Toggle::make('google_login_enabled')
                    ->label('Enable Google Login')
                    ->helperText('Show "Continue with Google" button on login and register pages.')
                    ->inline(false)
                    ->live(),
                Forms\Components\TextInput::make('google_client_id')
                    ->label('Client ID')
                    ->nullable()
                    ->visible(fn ($get) => $get('google_login_enabled'))
                    ->helperText('From Google Cloud Console → APIs & Services → Credentials.'),
                Forms\Components\TextInput::make('google_client_secret')
                    ->label('Client Secret')
                    ->password()
                    ->revealable()
                    ->nullable()
                    ->visible(fn ($get) => $get('google_login_enabled'))
                    ->helperText('Keep this secret. Never share it publicly.'),
            ])->columns(2),
        ])->statePath('oauth');
    }

    public function pixelForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Facebook / Meta Pixel')
                ->description('Tracks visitor behaviour and conversion events for Meta Ads.')
                ->schema([
                    Forms\Components\Toggle::make('facebook_pixel_enabled')
                        ->label('Enable Facebook Pixel')
                        ->helperText('Injects the base Pixel code on every storefront page.')
                        ->inline(false)
                        ->live()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('facebook_pixel_id')
                        ->label('Pixel ID')
                        ->placeholder('123456789012345')
                        ->nullable()
                        ->visible(fn ($get) => $get('facebook_pixel_enabled'))
                        ->helperText('Events Manager → Data Sources → your Pixel → Settings.'),
                ])->columns(2),
        ])->statePath('pixel');
    }

    public function chatForm(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('WhatsApp')->schema([
                Forms\Components\Toggle::make('chat_whatsapp_enabled')
                    ->label('Show WhatsApp button')
                    ->inline(false)
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('chat_whatsapp_number')
                    ->label('WhatsApp Number')
                    ->tel()
                    ->placeholder('01712345678')
                    ->nullable()
                    ->visible(fn ($get) => $get('chat_whatsapp_enabled'))
                    ->helperText('Bangladesh numbers can be entered as 01XXXXXXXXX. For other countries use the full international number.'),
            ])->columns(2),

            Forms\Components\Section::make('Messenger')->schema([
                Forms\Components\Toggle::make('chat_messenger_enabled')
                    ->label('Show Messenger button')
                    ->inline(false)
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('chat_messenger_id')
                    ->label('Facebook Page Username or ID')
                    ->placeholder('youthcollection')
                    ->nullable()
                    ->visible(fn ($get) => $get('chat_messenger_enabled'))
                    ->helperText('Paste your page link (https://m.me/yourpage or https://facebook.com/yourpage) or just the username. Messenger does not support pre-filled messages, so the product details are copied to the customer\'s clipboard to paste into the chat.'),
            ])->columns(2),

            Forms\Components\Section::make('Message')->schema([
                Forms\Components\Textarea::make('chat_message_template')
                    ->label('Pre-filled Message')
                    ->rows(5)
                    ->nullable()
                    ->columnSpanFull()
                    ->helperText('Available placeholders: {product_name}, {price}, {product_url}, {sku}. Leave blank to use the default message.'),
            ]),
        ])->statePath('chat');
    }

    public function save(): void
    {
        $this->generalForm->validate();
        $this->contactForm->validate();
        $this->homepageImageForm->validate();

        // Persist branding files manually.
        // $this->site_logo is either:
        //   - [uuid => TemporaryUploadedFile]  — a new upload waiting to be stored
        //   - ['settings/file.png' => 'settings/file.png']  — an already-stored file (no action needed)
        //   - []  — cleared / no image
        $logoPath = $this->storeOrKeep($this->site_logo, 'settings');
        $faviconPath = $this->storeOrKeep($this->site_favicon, 'settings');
        $promoImagePath = $this->storeOrKeep($this->promo_banner_image, 'banners');

        Setting::set('site_logo', $logoPath, 'branding');
        Setting::set('site_favicon', $faviconPath, 'branding');
        Setting::set('promo_banner_image', $promoImagePath, 'homepage');

        foreach ($this->general as $key => $value) {
            Setting::set($key, $value, 'general');
        }
        foreach ($this->contact as $key => $value) {
            Setting::set($key, $value, 'contact');
        }
        foreach ($this->payment as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value, 'payment');
        }

        foreach ($this->social as $key => $value) {
            Setting::set($key, $value, 'social');
        }
        foreach ($this->oauth as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value, 'oauth');
        }
        foreach ($this->homepage as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value, 'homepage');
        }

        $ogImagePath = $this->storeOrKeep($this->og_image, 'settings');
        Setting::set('og_image', $ogImagePath, 'seo');

        foreach ($this->seo as $key => $value) {
            Setting::set($key, $value, 'seo');
        }

        foreach ($this->pixel as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value, 'pixel');
        }

        foreach ($this->chat as $key => $value) {
            Setting::set($key, is_bool($value) ? ($value ? '1' : '0') : $value, 'chat');
        }

        Cache::forget('settings.public');

        Notification::make()->title('Settings saved successfully')->success()->send();
    }

    /**
     * Given the raw FileUpload state array, either:
     *  - store a new TemporaryUploadedFile to $directory on the public disk, or
     *  - return the existing path as-is, or
     *  - return '' if cleared.
     */
    private function storeOrKeep(?array $state, string $directory): string
    {
        if (empty($state)) {
            return '';
        }

        $first = array_values($state)[0];

        // New upload — Livewire TemporaryUploadedFile instance
        if ($first instanceof TemporaryUploadedFile) {
            $path = $first->store($directory, 'public');

            return $path ?: '';
        }

        // Already stored — the key is the path
        $key = array_key_first($state);
        if (is_string($key) && Storage::disk('public')->exists($key)) {
            return $key;
        }

        return '';
    }
}
