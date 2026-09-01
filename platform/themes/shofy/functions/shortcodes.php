<?php

use Botble\Base\Forms\FieldOptions\ColorFieldOption;
use Botble\Base\Forms\FieldOptions\CoreIconFieldOption;
use Botble\Base\Forms\FieldOptions\EditorFieldOption;
use Botble\Base\Forms\FieldOptions\MediaFileFieldOption;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\RadioFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\FieldOptions\UiSelectorFieldOption;
use Botble\Base\Forms\Fields\ColorField;
use Botble\Base\Forms\Fields\CoreIconField;
use Botble\Base\Forms\Fields\EditorField;
use Botble\Base\Forms\Fields\HtmlField;
use Botble\Base\Forms\Fields\MediaFileField;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\OnOffField;
use Botble\Base\Forms\Fields\RadioField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Base\Forms\Fields\UiSelectorField;
use Botble\Ecommerce\Models\Brand;
use Botble\Ecommerce\Models\Review;
use Botble\Setting\Models\Setting;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\SimpleSlider\Models\SimpleSliderItem;
use Botble\Newsletter\Forms\Fronts\NewsletterForm;
use Botble\Shortcode\Compilers\Shortcode as ShortcodeCompiler;
use Botble\Shortcode\Facades\Shortcode;
use Botble\Shortcode\Forms\FieldOptions\ShortcodeTabsFieldOption;
use Botble\Shortcode\Forms\Fields\ShortcodeTabsField;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Shortcode\ShortcodeField;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;
use Carbon\Carbon;
use Illuminate\Support\Arr;

app()->booted(function (): void {
    ThemeSupport::registerGoogleMapsShortcode(Theme::getThemeNamespace('partials.shortcodes.google-maps'));
    ThemeSupport::registerYoutubeShortcode();

    Shortcode::register('site-features', __('Site Features'), __('Site Features'), function (ShortcodeCompiler $shortcode) {
        $tabs = Shortcode::fields()->getTabsData(['title', 'description', 'icon'], $shortcode);

        return Theme::partial('shortcodes.site-features.index', compact('shortcode', 'tabs'));
    });

    Shortcode::setPreviewImage('site-features', Theme::asset()->url('images/shortcodes/site-features/style-1.png'));

    Shortcode::setAdminConfig('site-features', function (array $attributes) {
        $styles = [];

        foreach (range(1, 4) as $i) {
            $styles[$i] = [
                'label' => __('Style :number', ['number' => $i]),
                'image' => Theme::asset()->url(sprintf('images/shortcodes/site-features/style-%s.png', $i)),
            ];
        }

        return ShortcodeForm::createFromArray($attributes)
            ->add(
                'style',
                UiSelectorField::class,
                UiSelectorFieldOption::make()
                    ->choices($styles)
                    ->selected(Arr::get($attributes, 'style', 1))
            )
            ->add(
                'features',
                ShortcodeTabsField::class,
                ShortcodeTabsFieldOption::make()
                    ->fields([
                        'title' => [
                            'type' => 'text',
                            'title' => __('Title'),
                            'required' => true,
                        ],
                        'description' => [
                            'type' => 'textarea',
                            'title' => __('Description'),
                            'required' => false,
                        ],
                        'icon' => [
                            'type' => 'coreIcon',
                            'title' => __('Icon'),
                            'required' => true,
                        ],
                    ])
                    ->attrs($attributes)
            )
            ->add(
                'icon_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Icon color'))
                    ->defaultValue('#fd4b6b')
            );
    });

    Shortcode::register('app-downloads', __('App Downloads'), __('App Downloads'), function (ShortcodeCompiler $shortcode): ?string {
        $platforms = Shortcode::fields()->getTabsData(['image', 'url'], $shortcode);

        return Theme::partial('shortcodes.app-downloads.index', compact('shortcode', 'platforms'));
    });

    Shortcode::setPreviewImage('app-downloads', Theme::asset()->url('images/shortcodes/app-downloads.png'));

    Shortcode::setAdminConfig('app-downloads', function (array $attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'open_wrapper_google',
                HtmlField::class,
                ['html' => '<div class="form-fieldset">']
            )
            ->add(
                'google_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Google label'))
                    ->placeholder(__('Enter Google label'))
            )
            ->add(
                'google_icon',
                CoreIconField::class,
                CoreIconFieldOption::make()
                    ->label(__('Google Play icon'))
            )
            ->add(
                'google_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Google URL'))
                    ->placeholder(__('Enter Google URL'))
            )
            ->add('close_wrapper_google', HtmlField::class, ['html' => '</div>'])
            ->add('open_wrapper_apple', HtmlField::class, ['html' => '<div class="form-fieldset">'])
            ->add(
                'apple_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Apple label'))
                    ->placeholder(__('Enter Apple label'))
            )
            ->add(
                'apple_icon',
                CoreIconField::class,
                CoreIconFieldOption::make()
                    ->label(__('Apple icon'))
            )
            ->add(
                'apple_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Apple URL'))
                    ->placeholder(__('Enter Apple URL'))
            )
            ->add('close_wrapper_apple', HtmlField::class, ['html' => '</div>'])
            ->add(
                'screenshot',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Mobile screenshot'))
            )
            ->add(
                'shape_image_left',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Shape image left'))
            )
            ->add(
                'shape_image_right',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Shape image right'))
            );
    });

    Shortcode::register(
        'image-slider',
        __('Image Slider'),
        __('Dynamic carousel for featured content with customizable links.'),
        function (ShortcodeCompiler $shortcode) {
            $tabs = [];
            $brands = [];

            switch ($shortcode->type) {
                case 'custom':
                    $tabs = Shortcode::fields()->getTabsData(['name', 'image', 'url'], $shortcode);

                    if (empty($tabs)) {
                        return null;
                    }

                    break;

                case 'brands':
                    $brandIds = Shortcode::fields()->getIds('brand_ids', $shortcode);

                    if (empty($brandIds)) {
                        return null;
                    }

                    $brands = Brand::query()
                        ->wherePublished()
                        ->whereIn('id', $brandIds)
                        ->get();

                    if (empty($brands)) {
                        return null;
                    }

                    break;
            }

            return Theme::partial('shortcodes.image-slider.index', compact('shortcode', 'tabs', 'brands'));
        }
    );

    Shortcode::setPreviewImage('image-slider', Theme::asset()->url('images/shortcodes/image-slider.png'));

    Shortcode::setAdminConfig('image-slider', function (array $attributes) {
        $types = [
            'custom' => __('Custom'),
        ];

        if (is_plugin_active('ecommerce')) {
            $types['brands'] = __('Brands');
        }

        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'type',
                RadioField::class,
                RadioFieldOption::make()
                    ->label(__('Get data from to show'))
                    ->choices($types)
                    ->attributes([
                        'data-bb-toggle' => 'collapse',
                        'data-bb-target' => '.image-slider',
                    ]),
            )
            ->when(is_plugin_active('ecommerce'), function (ShortcodeForm $form) use ($attributes): void {
                $form->add(
                    'brand_ids',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Brands'))
                        ->choices(
                            Brand::query()
                                ->wherePublished()
                                ->pluck('name', 'id')
                                ->all()
                        )
                        ->selected(ShortcodeField::parseIds(Arr::get($attributes, 'brand_ids')))
                        ->searchable()
                        ->multiple()
                        ->wrapperAttributes([
                            'class' => 'mb-3 position-relative image-slider',
                            'data-bb-value' => 'brands',
                            'style' => sprintf('display: %s', Arr::get($attributes, 'type') === 'brands' ? 'block' : 'none'),
                        ]),
                );
            })
            ->add(
                'open_tabs_wrapper',
                HtmlField::class,
                ['html' => sprintf('<div class="image-slider" data-bb-value="custom" style="display: %s">', Arr::get($attributes, 'type') === 'custom' ? 'block' : 'none')]
            )
            ->add(
                'tabs',
                ShortcodeTabsField::class,
                ShortcodeTabsFieldOption::make()
                    ->fields([
                        'name' => [
                            'type' => 'text',
                            'title' => __('Name'),
                        ],
                        'image' => [
                            'type' => 'image',
                            'title' => __('Image'),
                            'required' => true,
                        ],
                        'url' => [
                            'type' => 'text',
                            'title' => __('URL'),
                        ],
                    ])
                    ->attrs($attributes)
            )
            ->add('close_tabs_wrapper', HtmlField::class, ['html' => '</div>'])
            ->add(
                'is_autoplay',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Is autoplay?'))
                    ->choices(['no' => __('No'), 'yes' => __('Yes')])
            )
            ->add(
                'autoplay_speed',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Autoplay speed (if autoplay enabled)'))
                    ->choices(
                        array_combine(
                            [2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000],
                            [2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000]
                        )
                    )
            )
            ->add(
                'is_loop',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Loop?'))
                    ->choices(['yes' => __('Continuously'), 'no' => __('Stop on the last slide')])
            );
    });

    Shortcode::register('about', __('About'), __('About'), function (ShortcodeCompiler $shortcode) {
        return Theme::partial('shortcodes.about.index', compact('shortcode'));
    });

    Shortcode::setPreviewImage('about', Theme::asset()->url('images/shortcodes/about.png'));

    Shortcode::setAdminConfig('about', function (array $attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->columns()
            ->add(
                'image_1',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image 1'))
            )
            ->add(
                'image_2',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image 2'))
            )
            ->add(
                'subtitle',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Subtitle'))
                    ->colspan(2)
            )
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
                    ->colspan(2)
            )
            ->add(
                'description',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(__('Description'))
                    ->colspan(2)
            )
            ->add(
                'action_label',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Action label')),
            )
            ->add(
                'action_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Action URL')),
            );
    });

    Shortcode::register('coming-soon', __('Coming Soon'), __('Coming Soon'), function (ShortcodeCompiler $shortcode): string {
        try {
            $countdownTime = Carbon::parse($shortcode->countdown_time);
        } catch (Exception) {
            $countdownTime = null;
        }

        $form = null;

        if (is_plugin_active('newsletter')) {
            $form = NewsletterForm::create();
        }

        return Theme::partial('shortcodes.coming-soon.index', compact('shortcode', 'countdownTime', 'form'));
    });

    Shortcode::setAdminConfig('coming-soon', function (array $attributes): ShortcodeForm {
        return ShortcodeForm::createFromArray($attributes)
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'countdown_time',
                'datetime',
                [
                    'label' => __('Countdown time'),
                    'default_value' => Carbon::now()->addDays(7)->format('Y-m-d H:i'),
                ]
            )
            ->add(
                'address',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Address'))
            )
            ->add(
                'hotline',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Hotline'))
            )
            ->add(
                'business_hours',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Business hours'))
            )
            ->add(
                'show_social_links',
                OnOffField::class,
                OnOffFieldOption::make()
                    ->label(__('Show social links'))
                    ->defaultValue(true)
            )
            ->add(
                'image',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
            );
    });

    Shortcode::register('banner', __('Banner'), __('Full width video banner with text overlay.'), function (ShortcodeCompiler $shortcode): ?string {
        if (! $shortcode->image) {
            return null;
        }

        return Theme::partial('shortcodes.banner.index', compact('shortcode'));
    });

    Shortcode::setAdminConfig('banner', function (array $attributes): ShortcodeForm {
        $normalizeEditorValue = function (?string $value): string {
            return str_replace(['“', '”', 'â€œ', 'â€', '&quot;', '&#34;'], '"', (string) $value);
        };

        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'image',
                MediaFileField::class,
                MediaFileFieldOption::make()
                    ->label(__('Video'))
                    ->value(Arr::get($attributes, 'image'))
                    ->required()
            )
            ->add(
                'slogan_1',
                EditorField::class,
                EditorFieldOption::make()
                    ->label(__('Slogan 1'))
                    ->value($normalizeEditorValue(Arr::get($attributes, 'slogan_1')))
                    ->rows(3)
            )
            ->add(
                'slogan_2',
                EditorField::class,
                EditorFieldOption::make()
                    ->label(__('Slogan 2'))
                    ->value($normalizeEditorValue(Arr::get($attributes, 'slogan_2')))
                    ->rows(3)
            )
            ->add(
                'slogan_3',
                EditorField::class,
                EditorFieldOption::make()
                    ->label(__('Slogan 3'))
                    ->value($normalizeEditorValue(Arr::get($attributes, 'slogan_3')))
                    ->rows(3)
            )
            ->add(
                'text_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Text color'))
                    ->defaultValue('#ffffff')
            )
            ->add(
                'font_family',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Font family'))
                    ->choices([
                        'inherit' => __('Default'),
                        'inter' => 'Inter',
                        'poppins' => 'Poppins',
                        'montserrat' => 'Montserrat',
                        'roboto' => 'Roboto',
                        'arial' => 'Arial',
                        'open-sans' => 'Open Sans',
                        'georgia' => 'Georgia',
                        'lora' => 'Lora',
                        'merriweather' => 'Merriweather',
                        'times-new-roman' => 'Times New Roman',
                        'helvetica-neue' => 'Helvetica Neue',
                        'playfair-display' => 'Playfair Display',
                        'cormorant-garamond' => 'Cormorant Garamond',
                        'dancing-script' => 'Dancing Script',
                        'great-vibes' => 'Great Vibes',
                    ])
                    ->selected(Arr::get($attributes, 'font_family', 'inherit'))
            )
            ->add(
                'position',
                SelectField::class,
                SelectFieldOption::make()
                    ->label(__('Text position'))
                    ->choices([
                        'left' => __('Left'),
                        'center' => __('Center'),
                        'right' => __('Right'),
                    ])
                    ->selected(Arr::get($attributes, 'position', 'center'))
            )
            ->add(
                'overlap_image',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Overlap image'))
                    ->value(Arr::get($attributes, 'overlap_image'))
            )
            ->add(
                'overlap_title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Overlap title'))
                    ->value(Arr::get($attributes, 'overlap_title'))
            )
            ->add(
                'overlap_text',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(__('Overlap text'))
                    ->value(Arr::get($attributes, 'overlap_text'))
                    ->rows(3)
            );
    });





    Shortcode::register('slider-three', __('slider-three'), __('slider-three'), function (ShortcodeCompiler $shortcode) {
        $sliders = SimpleSliderItem::query()
        ->where('simple_slider_id', 2)
        ->orderBy('order')
        ->get(['title', 'image', 'link', 'description']);
  
        return Theme::partial('shortcodes.slider-three.index', compact('shortcode', 'sliders'));
    });

    Shortcode::setPreviewImage('slider-three', Theme::asset()->url('images/shortcodes/site-features/style-1.png'));

    Shortcode::setAdminConfig('slider-three', function (array $attributes) {
     
        return ShortcodeForm::createFromArray($attributes)
            
            ->add(
                'icon_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Icon color'))
                    ->defaultValue('#fd4b6b')
            );
    });



    // about-flower

    Shortcode::register('about-flower', __('about-flower'), __('about-flower'), function (ShortcodeCompiler $shortcode) {
        $logo = Setting::where("key", "theme-shofy-logo")->value("value");
        return Theme::partial('shortcodes.about-flower.index', compact('shortcode','logo'));
    });

    Shortcode::setPreviewImage('about-flower', Theme::asset()->url('images/shortcodes/site-features/style-1.png'));

    Shortcode::setAdminConfig('about-flower', function (array $attributes) {
     
        return ShortcodeForm::createFromArray($attributes)
            ->add(
                'title1',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'title2',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
           ->add(
                'image1',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'image2',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'image3',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
             ->add(
                'image4',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )

            ->add(
                'icon1',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Icon'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'icon2',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Icon'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'icon3',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Icon'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'icon4',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Icon'))
                    ->helperText(__('Leave empty to use the category image'))
                    ->colspan(2)
            )
            ->add(
                'icon_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Icon color'))
                    ->defaultValue('#fd4b6b')
            );
    });


    //category
     Shortcode::register('ecommerce-category', __('ecommerce-category'), __('ecommerce-category'), function (ShortcodeCompiler $shortcode) {
        $categories = ProductCategory::query()
        ->where('is_featured', 1)               
        ->where('status', 'published') 
        ->with('slugable')         
        ->orderBy('order')
        ->get(['id', 'name', 'image']);  
        // dd($categories);
        if ($categories->isEmpty()) {
                return null;
        }
        return Theme::partial('shortcodes.ecommerce-category.index', compact('shortcode','categories'));
    });

    Shortcode::setPreviewImage('ecommerce-category', Theme::asset()->url('images/shortcodes/site-features/style-1.png'));

    Shortcode::setAdminConfig('ecommerce-category', function (array $attributes) {
     
        return ShortcodeForm::createFromArray($attributes)
            ->add(
                'kicker',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'heading',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
            )
            ->add(
                'background_color',
                ColorField::class,
                ColorFieldOption::make()
                    ->label(__('Background color'))
                    ->value(Arr::get($attributes, 'background_color', Arr::get($attributes, 'icon_color')))
                    ->defaultValue('#ffffff')
            );
    });


    //////////////////
    //  video-list
    //////////////////


    // danh sách url video youtube
    Shortcode::register('video-list', __('Video List'), __('Show list of YouTube videos'), function (ShortcodeCompiler $shortcode) {

        // Chuẩn hoá input video
        $raw = trim($shortcode->videos ?? '');

        // Tách tất cả link YouTube bằng regex (dù nằm trên 1 dòng hay nhiều dòng)
        preg_match_all(
            '/https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=[A-Za-z0-9_\-]+|youtu\.be\/[A-Za-z0-9_\-]+)/i',
            $raw,
            $matches
        );

        // Lấy danh sách URL duy nhất
        $urls = array_unique($matches[0] ?? []);

        if (empty($urls)) return null;

        // per_page từ shortcode
        $shortcode->per_page = (int) ($shortcode->per_page ?? 6);

        return Theme::partial('shortcodes.video-list.index', [
            'urls' => $urls,
            'shortcode' => $shortcode,
        ]);
    });




    Shortcode::setPreviewImage('video-list', Theme::asset()->url('images/shortcodes/video-list/preview.png'));

    Shortcode::setAdminConfig('video-list', function (array $attributes) {

        return ShortcodeForm::createFromArray($attributes)
            ->add('videos', 'textarea', [
                'label' => __('YouTube URLs'),
                'attr' => [
                    'rows' => 6,
                    'placeholder' => "Mỗi dòng 1 link YouTube...\nVí dụ:\nhttps://youtu.be/abc123\nhttps://www.youtube.com/watch?v=xyz456"
                ],
            ]);
    });



    Shortcode::register(
    'customer_reviews',
    'Customer Reviews',
    'Khối đánh giá của khách hàng',
    function ($shortcode) {

        $limit = (int) ($shortcode->limit ?? 6);

        $reviews = Review::query()
            ->where('star', 5)
            ->where('status', 'published')
            ->latest()
            ->limit($limit)
            ->get();

        return Theme::partial(
            'shortcodes.customer-reviews.customer-reviews',
            compact('reviews')
        );
    }
);

Shortcode::setAdminConfig('customer_reviews', function ($attributes) {
    return Theme::partial(
        'shortcodes.customer-reviews.customer-reviews-admin',
        compact('attributes')
    );
});

    
});
