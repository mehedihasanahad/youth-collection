<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Services\ProductChatService;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(string $slug, ProductChatService $chat): View
    {
        $locale = app()->getLocale();

        // Resolve slug with locale fallback
        $translation = ProductTranslation::where('slug', $slug)
            ->where('locale', $locale)
            ->first()
            ?? ProductTranslation::where('slug', $slug)
                ->where('locale', 'en')
                ->first();

        if (! $translation) {
            abort(404);
        }

        $product = Product::active()
            ->with([
                'translations',
                'images',
                'primaryImage',
                'brand',
                'category.translations',
                'variants.options',
                'variants.images',
            ])
            ->findOrFail($translation->product_id);

        // Load all approved reviews once; derive counts/averages in PHP
        $allApprovedReviews = $product->reviews()
            ->approved()
            ->with('user')
            ->latest()
            ->get();

        $reviews = $allApprovedReviews->take(10);
        $reviewCount = $allApprovedReviews->count();
        $avgRating = $reviewCount > 0 ? round($allApprovedReviews->avg('rating'), 1) : 0;

        $ratingCounts = array_fill_keys([1, 2, 3, 4, 5], 0);
        foreach ($allApprovedReviews as $r) {
            $ratingCounts[$r->rating]++;
        }

        // Related products: same category, exclude self
        $relatedProducts = Product::active()
            ->inStock()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['primaryImage', 'translations', 'brand'])
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        $currentTranslation = $product->getTranslation($locale);

        $chatLinks = $chat->linksFor($product, $locale);

        $isWishlisted = auth()->check()
            && auth()->user()->wishlist()->where('product_id', $product->id)->exists();

        $hasReviewed = auth()->check()
            && $product->reviews()->where('user_id', auth()->id())->exists();

        $userVotedReviewIds = auth()->check()
            ? \App\Models\ReviewVote::where('user_id', auth()->id())
                ->whereIn('review_id', $reviews->pluck('id'))
                ->pluck('review_id')
                ->toArray()
            : [];

        return view('product.show', compact(
            'product',
            'currentTranslation',
            'locale',
            'reviews',
            'reviewCount',
            'avgRating',
            'ratingCounts',
            'relatedProducts',
            'chatLinks',
            'isWishlisted',
            'hasReviewed',
            'userVotedReviewIds',
        ));
    }
}
